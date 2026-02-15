<?php

use Livewire\Volt\Component;
use App\Models\BudgetPlan;
use App\Models\BudgetPlanItem;
use App\Models\BudgetCategory;
use App\Models\StandardBudgetItem;
use App\Models\Level;
use App\Models\AcademicYear;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $level_filter = '';
    
    // Form Properties
    public ?BudgetPlan $editing = null;
    public $academic_year_id = '';
    public $title = '';
    public $notes = '';
    
    // Items Form
    public $formItems = []; // Array of ['standard_item_id', 'name', 'unit', 'quantity', 'amount', 'total', 'category_name']

    // Master Data Cache
    public $standardItems = [];
    
    public function mount()
    {
        $this->standardItems = StandardBudgetItem::with('category')->where('is_active', true)->get();
    }

    public function with(): array
    {
        $user = Auth::user();
        
        $query = BudgetPlan::with(['level', 'academicYear', 'submitter'])
            ->when($this->search, fn($q) => $q->where('title', 'like', "%{$this->search}%"));

        // User Access Control
        if ($user->isAdmin() || $user->isYayasan()) {
            $query->when($this->level_filter, fn($q) => $q->where('level_id', $this->level_filter));
        } else {
            // Treasurer/Kepsek only sees their managed level
            $query->where('level_id', $user->managed_level_id);
        }

        return [
            'plans' => $query->latest()->paginate(10),
            'levels' => Level::all(), // For filter (Admin)
            'years' => AcademicYear::where('is_active', true)->orWhere('status', 'open')->get(),
        ];
    }

    public function createNew(): void
    {
        $user = Auth::user();
        
        if (!$user->can('create', BudgetPlan::class) && !$user->managed_level_id && !$user->isAdmin()) {
             // Basic check if user has no level assigned and is not admin
             // Ideally use Policy, but for now simple check
             if (!$user->managed_level_id && !$user->isAdmin()) {
                 $this->dispatch('notify', variant: 'danger', message: 'Anda tidak memiliki akses untuk membuat RAB.');
                 return;
             }
        }

        $this->reset(['academic_year_id', 'title', 'editing', 'notes']);
        $this->formItems = [];
        $this->addItemRow(); // Start with 1 empty row
        $this->dispatch('open-plan-modal');
    }

    public function edit(BudgetPlan $plan): void
    {
        // Check access
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isYayasan() && $user->managed_level_id !== $plan->level_id) {
             return; 
        }

        $this->editing = $plan;
        $this->academic_year_id = $plan->academic_year_id;
        $this->title = $plan->title;
        $this->notes = $plan->notes;
        
        // Load Items
        $this->formItems = $plan->items->map(function($item) {
            return [
                'id' => $item->id, // Track existing ID
                'standard_item_id' => $item->standard_budget_item_id,
                'name' => $item->name,
                'unit' => $item->unit,
                'quantity' => $item->quantity,
                'amount' => $item->amount,
                'total' => $item->total,
                'category_name' => $item->standardItem->category->name ?? '-',
            ];
        })->toArray();

        $this->dispatch('open-plan-modal');
    }

    public function addItemRow(): void
    {
        $this->formItems[] = [
            'standard_item_id' => '',
            'name' => '',
            'unit' => '',
            'quantity' => 1,
            'amount' => 0,
            'total' => 0,
            'category_name' => '-',
        ];
    }

    public function removeItemRow($index): void
    {
        unset($this->formItems[$index]);
        $this->formItems = array_values($this->formItems); // Re-index
    }

    public function updatedFormItems($value, $key): void
    {
        // Parse key like "0.standard_item_id"
        $parts = explode('.', $key);
        if (count($parts) < 2) return;
        
        $index = $parts[0];
        $field = $parts[1];

        if ($field === 'standard_item_id') {
            $selectedItem = $this->standardItems->firstWhere('id', $value);
            if ($selectedItem) {
                $this->formItems[$index]['name'] = $selectedItem->name;
                $this->formItems[$index]['unit'] = $selectedItem->unit;
                $this->formItems[$index]['amount'] = $selectedItem->default_price ?? 0;
                $this->formItems[$index]['category_name'] = $selectedItem->category->name ?? '-';
                $this->calculateRowTotal($index);
            }
        } elseif ($field === 'quantity' || $field === 'amount') {
            $this->calculateRowTotal($index);
        }
    }

    public function calculateRowTotal($index): void
    {
        $qty = (int) ($this->formItems[$index]['quantity'] ?? 0);
        $amount = (float) ($this->formItems[$index]['amount'] ?? 0);
        $this->formItems[$index]['total'] = $qty * $amount;
    }

    public function getTotalAmountProperty()
    {
        return collect($this->formItems)->sum('total');
    }

    public function save(string $action = 'draft'): void
    {
        $user = Auth::user();

        $this->validate([
            'academic_year_id' => 'required',
            'title' => 'required|string|max:255',
            'formItems' => 'required|array|min:1',
            'formItems.*.standard_item_id' => 'required',
            'formItems.*.quantity' => 'required|integer|min:1',
            'formItems.*.amount' => 'required|numeric|min:0',
        ]);

        $levelId = $this->editing ? $this->editing->level_id : ($user->managed_level_id ?? Level::first()->id); 
        // Admin creates for first level if not managed (should refine for admin creation later)
        
        if ($this->editing) {
            $plan = $this->editing;
            $plan->update([
                'academic_year_id' => $this->academic_year_id,
                'title' => $this->title,
                'total_amount' => $this->getTotalAmountProperty(),
                'status' => $action === 'submit' ? 'submitted' : 'draft',
            ]);
            
            // Sync Items (Delete all and recreate is easiest for now, or careful sync)
            $plan->items()->delete();
        } else {
            $plan = BudgetPlan::create([
                'level_id' => $levelId,
                'academic_year_id' => $this->academic_year_id,
                'title' => $this->title,
                'total_amount' => $this->getTotalAmountProperty(),
                'status' => $action === 'submit' ? 'submitted' : 'draft',
                'submitted_by' => $user->id,
            ]);
        }

        foreach ($this->formItems as $item) {
            $plan->items()->create([
                'standard_budget_item_id' => $item['standard_item_id'],
                'name' => $item['name'] ?? 'Item',
                'quantity' => $item['quantity'],
                'unit' => $item['unit'] ?? 'Unit',
                'amount' => $item['amount'],
                'total' => $item['quantity'] * $item['amount'],
            ]);
        }

        if ($action === 'submit') {
            try {
                $plan->load(['items.standardItem.category', 'level', 'academicYear', 'submitter']); 

                // 1. Generate PDF
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.rab', ['plan' => $plan]);
                $pdfContent = $pdf->output();

                // 2. Find Targets (Admin + Yayasan with phone)
                $targets = \App\Models\User::whereNotNull('phone')
                   ->whereIn('role', ['admin', 'yayasan'])
                   ->get();
                
                // 3. Send via Fonnte
                $whatsapp = new \App\Services\WhatsApp\FonnteService();
                foreach ($targets as $target) {
                    $whatsapp->sendDocument(
                        $target->phone, 
                        $pdfContent, 
                        'RAB-' . $plan->id . '.pdf',
                        "Pengajuan RAB Baru:\nJudul: {$plan->title}\nOleh: {$user->name}\nTotal: Rp " . number_format($plan->total_amount, 0, ',', '.')
                    );
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('WA Notification Error: ' . $e->getMessage());
                // Don't block the UI flow, just log
            }
        }

        $this->dispatch('plan-saved');
        $this->dispatch('notify', variant: 'success', message: 'RAB berhasil disimpan.');
    }
    
    // Workflow Actions
    public function updateStatus(BudgetPlan $plan, string $status): void
    {
        // Authorization Check
        $user = Auth::user();
        if (!$user->isYayasan() && !$user->isAdmin()) {
             $this->dispatch('notify', variant: 'danger', message: 'Unauthorized action.');
             return;
        }
        
        $updateData = ['status' => $status];
        if (in_array($status, ['approved', 'rejected'])) {
            $updateData['approved_by'] = $user->id;
        }
        
        $plan->update($updateData);
        $this->dispatch('plan-saved'); // Refresh list
        $this->dispatch('notify', variant: 'success', message: "Status RAB diperbarui menjadi " . ucfirst($status));
    }

    public function delete(BudgetPlan $plan): void
    {
        if ($plan->status !== 'draft' && !Auth::user()->isAdmin()) {
             $this->dispatch('notify', variant: 'danger', message: 'Hanya draft yang dapat dihapus.');
             return;
        }
        $plan->delete();
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
        <div>
            <flux:heading size="xl">RAB / Rencana Anggaran Biaya</flux:heading>
            <flux:subheading>Kelola pengajuan dan persetujuan anggaran.</flux:subheading>
        </div>
        @if(Auth::user()->isTreasurer() || Auth::user()->isHeadmaster() || Auth::user()->isAdmin())
            <flux:button variant="primary" icon="plus" wire:click="createNew">Buat RAB Baru</flux:button>
        @endif
    </div>

    <!-- Filters -->
    <div class="flex flex-col md:flex-row gap-4 mb-6 items-center justify-between">
        <div class="flex gap-2 w-full md:w-auto">
            <flux:input wire:model.live="search" icon="magnifying-glass" placeholder="Cari RAB..." class="w-full md:w-64" />
            
            @if(Auth::user()->isAdmin() || Auth::user()->isYayasan())
                <flux:select wire:model.live="level_filter" placeholder="Semua Jenjang" class="w-full md:w-48">
                    <option value="">Semua Jenjang</option>
                    @foreach($levels as $level)
                        <option value="{{ $level->id }}">{{ $level->name }}</option>
                    @endforeach
                </flux:select>
            @endif
        </div>
    </div>

    <!-- List -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead>
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Judul</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Jenjang</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Tahun Ajaran</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-zinc-500 uppercase tracking-wider">Total Anggaran</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-zinc-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach($plans as $plan)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $plan->title }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-zinc-500">{{ $plan->level->name }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-zinc-500">{{ $plan->academicYear->name }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-bold text-zinc-900 dark:text-zinc-100">
                            Rp {{ number_format($plan->total_amount, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-center">
                            @php
                                $variant = match($plan->status) {
                                    'draft' => 'zinc',
                                    'submitted' => 'warning',
                                    'approved' => 'success',
                                    'transferred' => 'info',
                                    'rejected' => 'danger',
                                };
                            @endphp
                            <flux:badge variant="{{ $variant }}" size="sm">{{ ucfirst($plan->status) }}</flux:badge>
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <!-- View/Edit -->
                            <flux:button size="sm" variant="ghost" icon="eye" wire:click="edit({{ $plan->id }})" />
                            
                            <!-- Delete (Draft Only) -->
                            @if($plan->status === 'draft')
                                <flux:button size="sm" variant="ghost" icon="trash" class="text-red-500" wire:confirm="Hapus RAB ini?" wire:click="delete({{ $plan->id }})" />
                            @endif
                            
                            <!-- Approval Actions (Yayasan) -->
                            @if((Auth::user()->isYayasan() || Auth::user()->isAdmin()) && $plan->status === 'submitted')
                                <flux:button size="sm" variant="primary" icon="check" wire:click="updateStatus({{ $plan->id }}, 'approved')" />
                                <flux:button size="sm" variant="danger" icon="x-mark" wire:click="updateStatus({{ $plan->id }}, 'rejected')" />
                            @endif
                            
                            <!-- Transfer Action (Yayasan) -->
                            @if((Auth::user()->isYayasan() || Auth::user()->isAdmin()) && $plan->status === 'approved')
                                <flux:button size="sm" variant="primary" icon="banknotes" wire:click="updateStatus({{ $plan->id }}, 'transferred')" title="Tandai Sudah Transfer" />
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <div class="mt-4">
        {{ $plans->links() }}
    </div>

    <!-- RAB Modal (Fullscreen or Large) -->
    <flux:modal name="plan-modal" class="min-w-[800px] max-h-[90vh] overflow-y-auto" @open-plan-modal.window="$flux.modal('plan-modal').show()" x-on:plan-saved.window="$flux.modal('plan-modal').close()">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editing ? 'Edit RAB' : 'Buat RAB Baru' }}</flux:heading>
                <flux:subheading>Anggaran diajukan oleh Bendahara/Kepsek untuk disetujui Yayasan.</flux:subheading>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:select wire:model="academic_year_id" label="Tahun Ajaran" placeholder="Pilih Tahun Ajaran">
                    @foreach($years as $year)
                        <option value="{{ $year->id }}">{{ $year->name }}</option>
                    @endforeach
                </flux:select>
                
                <flux:input wire:model="title" label="Judul RAB" placeholder="Contoh: RAB Operasional Januari 2026" />
            </div>

            <!-- Items Table -->
            <div class="border rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-zinc-200">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 uppercase">Item (Ketik utk cari)</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 uppercase">Kategori</th>
                            <th class="px-3 py-2 text-center text-xs font-medium text-zinc-500 uppercase w-24">Qty</th>
                            <th class="px-3 py-2 text-center text-xs font-medium text-zinc-500 uppercase w-24">Satuan</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-zinc-500 uppercase w-32">Harga Satuan</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-zinc-500 uppercase w-32">Total</th>
                            <th class="px-3 py-2 w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-900">
                        @foreach($formItems as $index => $item)
                        <tr wire:key="item-{{ $index }}">
                            <td class="px-3 py-2">
                                <flux:select wire:model.live="formItems.{{ $index }}.standard_item_id" placeholder="Pilih Item" searchable>
                                    @foreach($standardItems as $std)
                                        <option value="{{ $std->id }}">{{ $std->name }}</option>
                                    @endforeach
                                </flux:select>
                            </td>
                            <td class="px-3 py-2 text-sm text-zinc-500">{{ $item['category_name'] }}</td>
                            <td class="px-3 py-2">
                                <flux:input type="number" wire:model.live="formItems.{{ $index }}.quantity" class="text-center" min="1" />
                            </td>
                            <td class="px-3 py-2 text-sm text-center text-zinc-500">{{ $item['unit'] }}</td>
                            <td class="px-3 py-2">
                                <flux:input type="number" wire:model.live="formItems.{{ $index }}.amount" class="text-right" min="0" />
                            </td>
                            <td class="px-3 py-2 text-right font-medium">
                                Rp {{ number_format($item['total'], 0, ',', '.') }}
                            </td>
                            <td class="px-3 py-2 text-center">
                                <button type="button" wire:click="removeItemRow({{ $index }})" class="text-red-500 hover:text-red-700">
                                    <flux:icon name="trash" class="size-4" />
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-zinc-50 dark:bg-zinc-800 font-bold">
                        <tr>
                            <td colspan="5" class="px-4 py-3 text-right">Total Anggaran</td>
                            <td class="px-4 py-3 text-right">Rp {{ number_format($this->totalAmount, 0, ',', '.') }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
                <div class="p-2 bg-zinc-50 dark:bg-zinc-800 border-t">
                    <flux:button size="sm" icon="plus" wire:click="addItemRow">Tambah Baris</flux:button>
                </div>
            </div>

            <flux:textarea wire:model="notes" label="Catatan" placeholder="Catatan tambahan..." />
            
            <div class="flex justify-between items-center pt-4 border-t">
                <div>
                    @if($editing && ($editing->status === 'submitted' && (Auth::user()->isYayasan() || Auth::user()->isAdmin())))
                        <div class="flex gap-2">
                            <flux:button variant="primary" wire:click="updateStatus({{ $editing->id }}, 'approved')">Setujui (Approve)</flux:button>
                            <flux:button variant="danger" wire:click="updateStatus({{ $editing->id }}, 'rejected')">Tolak (Reject)</flux:button>
                        </div>
                    @endif
                </div>
                <div class="flex gap-2">
                    <flux:button variant="ghost" x-on:click="$flux.modal('plan-modal').close()">Tutup</flux:button>
                    @if(!$editing || $editing->status === 'draft' || $editing->status === 'rejected')
                        <flux:button variant="subtle" wire:click="save('draft')">Simpan Draft</flux:button>
                        <flux:button variant="primary" wire:click="save('submit')">Simpan & Ajukan</flux:button>
                    @endif
                </div>
            </div>
        </div>
    </flux:modal>
</div>
