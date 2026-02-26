<?php

use Livewire\Component;
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
    public $level_id = '';
    public $title = '';
    public $notes = '';
    
    // Items Form
    public $formItems = []; // Array of ['standard_item_id', 'name', 'unit', 'quantity', 'amount', 'total', 'category_name']
    public $itemSearches = []; // Per row search input

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

        $this->reset(['title', 'editing', 'notes', 'level_id']);
        $this->academic_year_id = AcademicYear::where('is_active', true)->first()?->id ?? '';
        $this->level_id = $user->managed_level_id ?? Level::first()?->id ?? '';
        $this->formItems = [];
        $this->itemSearches = [''];
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
        $this->level_id = $plan->level_id;
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

        $this->itemSearches = collect($this->formItems)->pluck('name')->toArray();

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
        $this->itemSearches[] = '';
    }

    public function removeItemRow($index): void
    {
        unset($this->formItems[$index]);
        unset($this->itemSearches[$index]);
        $this->formItems = array_values($this->formItems); // Re-index
        $this->itemSearches = array_values($this->itemSearches); // Re-index
    }

    public function updatedFormItems($value, $key): void
    {
        // Parse key like "0.standard_item_id"
        $parts = explode('.', $key);
        if (count($parts) < 2) return;
        
        $index = $parts[0];
        $field = $parts[1];

        if ($field === 'standard_item_id') {
            if ($value && is_numeric($value)) {
                $selectedItem = $this->standardItems->firstWhere('id', (int) $value);
                if ($selectedItem) {
                    $this->formItems[$index]['name'] = $selectedItem->name;
                    $this->formItems[$index]['unit'] = $selectedItem->unit;
                    $this->formItems[$index]['amount'] = $selectedItem->default_price ?? 0;
                    $this->formItems[$index]['category_name'] = $selectedItem->category->name ?? '-';
                    $this->calculateRowTotal($index);
                    $this->itemSearches[$index] = $selectedItem->name;
                }
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
            'level_id' => 'required|exists:levels,id',
            'title' => 'required|string|max:255',
            'formItems' => 'required|array|min:1',
            'formItems.*.name' => 'required|string|max:255',
            'formItems.*.standard_item_id' => 'nullable',
            'formItems.*.quantity' => 'required|integer|min:1',
            'formItems.*.amount' => 'required|numeric|min:0',
        ]);

        if ($this->editing) {
            $plan = $this->editing;
            $plan->update([
                'academic_year_id' => $this->academic_year_id,
                'level_id' => $this->level_id,
                'title' => $this->title,
                'total_amount' => $this->getTotalAmountProperty(),
                'status' => $action === 'submit' ? 'submitted' : 'draft',
            ]);
            
            // Sync Items (Delete all and recreate is easiest for now, or careful sync)
            $plan->items()->delete();
        } else {
            $plan = BudgetPlan::create([
                'level_id' => $this->level_id,
                'academic_year_id' => $this->academic_year_id,
                'title' => $this->title,
                'total_amount' => $this->getTotalAmountProperty(),
                'status' => $action === 'submit' ? 'submitted' : 'draft',
                'submitted_by' => $user->id,
            ]);
        }

        foreach ($this->formItems as $item) {
            $plan->items()->create([
                'standard_budget_item_id' => is_numeric($item['standard_item_id']) ? $item['standard_item_id'] : null,
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
                $profile = \App\Models\SchoolProfile::first();
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.rab', ['plan' => $plan, 'profile' => $profile]);
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
                        "Assalamu'alaikum Warahmatullahi Wabarakatuh\nPengajuan RAB Baru:\nJudul: {$plan->title}\nOleh: {$user->name}\nTotal: Rp " . number_format($plan->total_amount, 0, ',', '.')
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

        if (in_array($status, ['approved', 'transferred'])) {
            $updateData['is_active'] = true;
            BudgetPlan::where('level_id', $plan->level_id)
                ->where('academic_year_id', $plan->academic_year_id)
                ->where('id', '!=', $plan->id)
                ->update(['is_active' => false]);
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

    public function createSubItem($index, $name): void
    {
        $this->formItems[$index]['name'] = $name;
        $this->formItems[$index]['standard_item_id'] = ''; // Ensure it's empty/null for manual items
        $this->formItems[$index]['category_name'] = 'Manual / Lainnya';
        $this->itemSearches[$index] = $name;
    }

    public function exportPdf(BudgetPlan $plan)
    {
        $plan->load(['items.standardItem.category', 'level', 'academicYear', 'submitter', 'approver']);
        $profile = \App\Models\SchoolProfile::first();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.rab', [
            'plan' => $plan,
            'profile' => $profile,
        ]);

        return response()->streamDownload(
            fn () => print($pdf->output()),
            'RAB-' . str($plan->title)->slug() . '.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-header title="RAB / Rencana Anggaran Biaya" subtitle="Kelola pengajuan dan persetujuan anggaran." separator>
        <x-slot:actions>
            @if(Auth::user()->isTreasurer() || Auth::user()->isHeadmaster() || Auth::user()->isAdmin())
                <x-button label="Buat RAB Baru" icon="o-plus" class="btn-primary" wire:click="createNew" />
            @endif
        </x-slot:actions>
    </x-header>

    <!-- Filters -->
    <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
        <div class="flex gap-2 w-full md:w-auto">
            <x-input wire:model.live="search" icon="o-magnifying-glass" placeholder="Cari RAB..." class="w-full md:w-64" />
            
            @if(Auth::user()->isAdmin() || Auth::user()->isYayasan())
                <x-select 
                    wire:model.live="level_filter" 
                    placeholder="Semua Jenjang" 
                    :options="$levels" 
                    class="w-full md:w-48" 
                />
            @endif
        </div>
    </div>

    <!-- List -->
    <x-card shadow>
        <x-table :headers="[
            ['key' => 'title', 'label' => 'Judul'],
            ['key' => 'level.name', 'label' => 'Jenjang'],
            ['key' => 'academicYear.name', 'label' => 'Tahun Ajaran'],
            ['key' => 'total_amount', 'label' => 'Total Anggaran', 'class' => 'text-right font-bold'],
            ['key' => 'status', 'label' => 'Status', 'class' => 'text-center'],
            ['key' => 'actions', 'label' => 'Aksi', 'class' => 'text-right']
        ]" :rows="$plans" with-pagination>
            @scope('cell_total_amount', $plan)
                Rp {{ number_format($plan->total_amount, 0, ',', '.') }}
            @endscope

            @scope('cell_status', $plan)
                @php
                    $color = match($plan->status) {
                        'draft' => 'badge-ghost',
                        'submitted' => 'badge-warning',
                        'approved' => 'badge-success',
                        'transferred' => 'badge-info',
                        'rejected' => 'badge-error',
                    };
                @endphp
                <x-badge :label="ucfirst($plan->status)" class="{{ $color }} badge-sm" />
            @endscope

            @scope('cell_actions', $plan)
                <div class="flex justify-end gap-1">
                    <!-- View/Edit -->
                    <x-button icon="o-eye" wire:click="edit({{ $plan->id }})" ghost sm />

                    <!-- Preview PDF -->
                    <x-button icon="o-printer" wire:click="exportPdf({{ $plan->id }})" ghost sm />
                    
                    <!-- Delete (Draft Only) -->
                    @if($plan->status === 'draft' || Auth::user()->isAdmin())
                        <x-button 
                            icon="o-trash" 
                            class="text-error" 
                            wire:confirm="Hapus RAB ini?" 
                            wire:click="delete({{ $plan->id }})" 
                            ghost sm 
                        />
                    @endif
                    
                    <!-- Approval Actions (Yayasan) -->
                    @if((Auth::user()->isYayasan() || Auth::user()->isAdmin()) && $plan->status === 'submitted')
                        <x-button icon="o-check" class="btn-success btn-sm" wire:click="updateStatus({{ $plan->id }}, 'approved')" />
                        <x-button icon="o-x-mark" class="btn-error btn-sm" wire:click="updateStatus({{ $plan->id }}, 'rejected')" />
                    @endif
                    
                    <!-- Transfer Action (Yayasan) -->
                    @if((Auth::user()->isYayasan() || Auth::user()->isAdmin()) && $plan->status === 'approved')
                        <x-button label="Transfer" icon="o-banknotes" class="btn-primary btn-sm" wire:click="updateStatus({{ $plan->id }}, 'transferred')" title="Tandai Sudah Transfer" />
                    @endif
                </div>
            @endscope
        </x-table>
    </x-card>

    <!-- RAB Modal (Large) -->
    <x-modal id="plan-modal" class="backdrop-blur" persistent>
        <x-header :title="$editing ? 'Edit RAB' : 'Buat RAB Baru'" subtitle="Anggaran diajukan oleh Bendahara/Kepsek untuk disetujui Yayasan." separator />

        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                <x-select wire:model="academic_year_id" label="Tahun Ajaran" placeholder="Pilih Tahun Ajaran" :options="$years" />
                <x-select wire:model="level_id" label="Jenjang / Level" placeholder="Pilih Jenjang" :options="$levels" />
                <x-input wire:model="title" label="Judul RAB" placeholder="Contoh: RAB Operasional Januari 2026" />
            </div>

            <!-- Items Table -->
            <div class="border rounded-lg bg-base-200 p-0.5 overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th class="bg-base-300">Item Standar / Baru</th>
                            <th class="bg-base-300 w-32">Kategori</th>
                            <th class="bg-base-300 text-center w-28">Qty</th>
                            <th class="bg-base-300 text-center w-24">Satuan</th>
                            <th class="bg-base-300 text-right w-40">Harga Satuan</th>
                            <th class="bg-base-300 text-right w-40">Total</th>
                            <th class="bg-base-300 w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-base-100">
                        @foreach($formItems as $index => $item)
                        <tr wire:key="item-{{ $index }}">
                            <td>
                                <div class="relative" 
                                    x-data="{ 
                                        open: false, 
                                        search: @entangle('itemSearches.' . $index),
                                        options: {{ $standardItems->map(fn($i) => ['id' => $i->id, 'name' => $i->name])->toJson() }},
                                        get filteredOptions() {
                                            if (!this.search) return this.options.slice(0, 10);
                                            return this.options.filter(o => o.name.toLowerCase().includes(this.search.toLowerCase())).slice(0, 10);
                                        },
                                        get exactMatch() {
                                            return this.options.some(o => o.name.toLowerCase() === this.search.toLowerCase());
                                        },
                                        select(opt) {
                                            $wire.set('formItems.{{ $index }}.standard_item_id', opt.id);
                                            this.search = opt.name;
                                            this.open = false;
                                        },
                                        create() {
                                            $wire.createSubItem({{ $index }}, this.search);
                                            this.open = false;
                                        }
                                    }"
                                    x-on:click.away="open = false"
                                >
                                    <x-input 
                                        x-model="search" 
                                        x-on:focus="open = true"
                                        x-on:input="open = true"
                                        placeholder="Cari atau ketik nama baru..."
                                        sm
                                    />
                                    
                                    <div x-show="open" 
                                        class="absolute z-50 w-full mt-1 bg-base-100 border border-base-300 rounded shadow-lg max-h-60 overflow-auto"
                                        x-transition
                                        style="display: none;"
                                    >
                                        <template x-for="opt in filteredOptions" :key="opt.id">
                                            <div x-on:click="select(opt)" 
                                                class="px-3 py-2 hover:bg-base-200 cursor-pointer text-sm"
                                                x-text="opt.name">
                                            </div>
                                        </template>
                                        
                                        <div x-show="search && search.length > 1 && !exactMatch" 
                                            x-on:click="create()"
                                            class="px-3 py-2 hover:bg-base-200 cursor-pointer text-sm border-t font-medium text-emerald-600 flex items-center gap-2"
                                        >
                                            <x-icon name="o-plus" class="size-3" />
                                            <span>Tambah "<span x-text="search"></span>"</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-xs opacity-50">{{ $item['category_name'] }}</td>
                            <td class="text-center">
                                <x-input type="number" wire:model.live.debounce.1000ms="formItems.{{ $index }}.quantity" class="text-center w-28 mx-auto" min="1" sm />
                            </td>
                            <td class="text-center">
                                <x-input wire:model="formItems.{{ $index }}.unit" placeholder="Satuan" class="text-center w-16 mx-auto" sm />
                            </td>
                            <td>
                                <x-input type="number" wire:model.live.debounce.1300ms="formItems.{{ $index }}.amount" class="text-right w-32 ml-auto" min="0" sm />
                            </td>
                            <td class="text-right font-medium">
                                <div wire:loading.remove wire:target="formItems.{{ $index }}.quantity, formItems.{{ $index }}.amount">
                                    Rp {{ number_format($item['total'], 0, ',', '.') }}
                                </div>
                                <div wire:loading wire:target="formItems.{{ $index }}.quantity, formItems.{{ $index }}.amount">
                                    <span class="loading loading-spinner loading-xs text-info"></span>
                                </div>
                            </td>
                            <td class="text-center">
                                <x-button icon="o-trash" class="text-error" wire:click="removeItemRow({{ $index }})" ghost sm />
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-base-300 font-bold">
                        <tr>
                            <td colspan="5" class="text-right">Total Anggaran</td>
                            <td class="text-right">Rp {{ number_format($this->totalAmount, 0, ',', '.') }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
                <div class="p-2 bg-base-200 border-t border-base-300">
                    <x-button label="Tambah Baris" icon="o-plus" wire:click="addItemRow" sm />
                </div>
            </div>

            <x-textarea wire:model="notes" label="Catatan" placeholder="Catatan tambahan..." />
            
            <x-slot:actions>
                <div class="flex flex-wrap justify-between items-center w-full gap-4">
                    <div class="flex gap-2">
                        @if($editing && ($editing->status === 'submitted' && (Auth::user()->isYayasan() || Auth::user()->isAdmin())))
                            <x-button label="Setujui (Approve)" class="btn-primary" wire:click="updateStatus({{ $editing->id }}, 'approved')" />
                            <x-button label="Tolak (Reject)" class="btn-error" wire:click="updateStatus({{ $editing->id }}, 'rejected')" />
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <x-button label="Tutup" @click="$dispatch('close-modal', 'plan-modal')" />
                        @if(!$editing || $editing->status === 'draft' || $editing->status === 'rejected')
                            <x-button label="Simpan Draft" class="btn-outline btn-primary" wire:click="save('draft')" spinner="save" />
                            <x-button label="Simpan & Ajukan" class="btn-primary" wire:click="save('submit')" spinner="save" />
                        @endif
                    </div>
                </div>
            </x-slot:actions>
        </div>
    </x-modal>
</div>
</div>
