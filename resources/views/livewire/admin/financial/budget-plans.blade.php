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
    public bool $planModal = false;
    
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
             if (!$user->managed_level_id && !$user->isAdmin()) {
                 session()->flash('error', 'Anda tidak memiliki akses untuk membuat RAB.');
                 return;
             }
        }

        $this->reset(['title', 'editing', 'notes', 'level_id']);
        $this->academic_year_id = AcademicYear::where('is_active', true)->first()?->id ?? '';
        $this->level_id = $user->managed_level_id ?? Level::first()?->id ?? '';
        $this->formItems = [];
        $this->itemSearches = [''];
        $this->addItemRow(); // Start with 1 empty row
        $this->planModal = true;
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
        $this->planModal = true;
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
            
            // Sync Items
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
            }
        }

        $this->planModal = false;
        session()->flash('success', 'RAB berhasil disimpan.');
    }
    
    // Workflow Actions
    public function updateStatus(BudgetPlan $plan, string $status): void
    {
        $user = Auth::user();
        if (!$user->isYayasan() && !$user->isAdmin()) {
             session()->flash('error', 'Unauthorized action.');
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
        session()->flash('success', 'Status RAB diperbarui menjadi ' . ucfirst($status));
        $this->planModal = false;
    }

    public function delete(BudgetPlan $plan): void
    {
        if ($plan->status !== 'draft' && !Auth::user()->isAdmin()) {
             session()->flash('error', 'Hanya draft yang dapat dihapus.');
             return;
        }
        $plan->delete();
        session()->flash('success', 'RAB berhasil dihapus.');
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

<div class="p-6 space-y-5 text-slate-900 dark:text-white pb-24 md:pb-6">
    @if (session('success'))
        <x-ui.alert :title="__('Sukses')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100" dismissible>
            {{ session('success') }}
        </x-ui.alert>
    @endif

    @if (session('error'))
        <x-ui.alert :title="__('Gagal')" icon="o-exclamation-circle" class="bg-rose-50 text-rose-800 border-rose-100" dismissible>
            {{ session('error') }}
        </x-ui.alert>
    @endif

    <x-ui.header :title="__('RAB / Rencana Anggaran Biaya')" :subtitle="__('Kelola pengajuan, persetujuan, dan pencairan anggaran operasional sekolah.')" separator>
        <x-slot:actions>
            @if(Auth::user()->isTreasurer() || Auth::user()->isHeadmaster() || Auth::user()->isAdmin())
                <x-ui.button :label="__('Buat RAB Baru')" icon="o-plus" class="btn-primary" wire:click="createNew" />
            @endif
        </x-slot:actions>
    </x-ui.header>

    <div class="flex flex-col md:flex-row gap-4">
        <div class="flex-1">
            <x-ui.input 
                wire:model.live.debounce.300ms="search" 
                :placeholder="__('Cari judul RAB...')" 
                icon="o-magnifying-glass" 
            />
        </div>
        
        @if(Auth::user()->isAdmin() || Auth::user()->isYayasan())
            <x-ui.select 
                wire:model.live="level_filter" 
                :placeholder="__('Semua Jenjang')" 
                :options="$levels" 
                class="w-full md:w-48" 
            />
        @endif
    </div>

    <x-ui.card shadow padding="false">
        <x-ui.table 
            :headers="[
                ['key' => 'title_info', 'label' => __('Judul / Tahun')],
                ['key' => 'level_name', 'label' => __('Jenjang')],
                ['key' => 'amount_label', 'label' => __('Total Anggaran'), 'class' => 'text-right'],
                ['key' => 'status_label', 'label' => __('Status'), 'class' => 'text-center'],
                ['key' => 'actions', 'label' => __('Aksi'), 'class' => 'text-right']
            ]" 
            :rows="$plans"
        >
            @scope('cell_title_info', $plan)
                <div class="flex flex-col">
                    <span class="font-bold text-slate-900 dark:text-white">{{ $plan->title }}</span>
                    <span class="text-[10px] text-slate-400 font-mono tracking-tighter uppercase">{{ $plan->academicYear?->name ?? '-' }}</span>
                </div>
            @endscope

            @scope('cell_level_name', $plan)
                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">{{ $plan->level?->name ?? '-' }}</span>
            @endscope

            @scope('cell_amount_label', $plan)
                <span class="font-mono text-sm font-black text-slate-900 dark:text-white italic tracking-tighter">
                    Rp {{ number_format($plan->total_amount, 0, ',', '.') }}
                </span>
            @endscope

            @scope('cell_status_label', $plan)
                @php
                    $statusClass = match($plan->status) {
                        'draft' => 'bg-slate-100 text-slate-600',
                        'submitted' => 'bg-amber-100 text-amber-700',
                        'approved' => 'bg-emerald-100 text-emerald-700',
                        'transferred' => 'bg-blue-100 text-blue-700',
                        'rejected' => 'bg-rose-100 text-rose-700',
                        default => 'bg-slate-100 text-slate-500'
                    };
                @endphp
                <x-ui.badge :label="strtoupper($plan->status)" class="{{ $statusClass }} border-none text-[8px] font-black px-2 py-0.5" />
            @endscope

            @scope('cell_actions', $plan)
                <div class="flex justify-end gap-2">
                    <x-ui.button icon="o-eye" wire:click="edit({{ $plan->id }})" class="btn-ghost btn-sm text-slate-400 hover:text-primary transition-colors" />
                    <x-ui.button icon="o-printer" wire:click="exportPdf({{ $plan->id }})" class="btn-ghost btn-sm text-slate-400 hover:text-slate-600 transition-colors" />
                    @if($plan->status === 'draft' || Auth::user()->isAdmin())
                        <x-ui.button icon="o-trash" wire:click="delete({{ $plan->id }})" wire:confirm="{{ __('Hapus RAB ini?') }}" class="btn-ghost btn-sm text-slate-400 hover:text-rose-600 transition-colors" />
                    @endif
                </div>
            @endscope
        </x-ui.table>

        @if($plans->isEmpty())
            <div class="py-12 text-center text-slate-400 italic text-sm">
                {{ __('Tidak ada data RAB yang ditemukan.') }}
            </div>
        @endif

        <div class="p-4 border-t border-slate-100 dark:border-slate-800">
            {{ $plans->links() }}
        </div>
    </x-ui.card>

    <x-ui.modal wire:model="planModal" maxWidth="max-w-5xl">
        <x-ui.header :title="$editing ? __('Edit RAB') : __('Buat RAB Baru')" :subtitle="__('Rencana Anggaran diajukan untuk disetujui oleh Yayasan.')" separator />

        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-ui.select wire:model="academic_year_id" :label="__('Tahun Ajaran')" :placeholder="__('Pilih Tahun')" :options="$years" required />
                <x-ui.select wire:model="level_id" :label="__('Jenjang / Level')" :placeholder="__('Pilih Jenjang')" :options="$levels" required />
                <x-ui.input wire:model="title" :label="__('Judul RAB')" :placeholder="__('Contoh: Operasional Januari 2026')" required />
            </div>

            <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-900/30 overflow-hidden">
                <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-white dark:bg-slate-900">
                    <span class="text-[11px] font-black uppercase text-slate-400 tracking-widest italic">{{ __('Daftar Rincian Item Anggaran') }}</span>
                    <x-ui.button :label="__('Tambah Baris')" icon="o-plus" wire:click="addItemRow" class="btn-sm btn-ghost text-xs font-black uppercase" />
                </div>
                
                <div class="overflow-x-auto min-h-[200px]">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead class="bg-slate-100/50 dark:bg-slate-800/50 text-[10px] font-black uppercase text-slate-500 tracking-tighter">
                            <tr>
                                <th class="px-3 py-2.5">{{ __('Item Deskripsi') }}</th>
                                <th class="px-3 py-2.5 text-center w-20">{{ __('Qty') }}</th>
                                <th class="px-3 py-2.5 text-center w-20">{{ __('Satuan') }}</th>
                                <th class="px-3 py-2.5 text-right w-36">{{ __('Harga Satuan') }}</th>
                                <th class="px-3 py-2.5 text-right w-32">{{ __('Total') }}</th>
                                <th class="px-3 py-2.5 w-8"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($formItems as $index => $item)
                            <tr wire:key="item-{{ $index }}" class="hover:bg-white/50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="px-3 py-2">
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
                                        <x-ui.input 
                                            x-model="search" 
                                            x-on:focus="open = true"
                                            x-on:input="open = true"
                                            :placeholder="__('Cari atau ketik item baru...')"
                                            class="!py-1 !text-xs"
                                        />
                                        
                                        <div x-show="open" 
                                            class="absolute z-[60] w-full mt-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-2xl max-h-48 overflow-auto"
                                            x-transition
                                            style="display: none;"
                                        >
                                            <template x-for="opt in filteredOptions" :key="opt.id">
                                                <div x-on:click="select(opt)" 
                                                    class="px-3 py-1.5 hover:bg-slate-50 dark:hover:bg-slate-800 cursor-pointer text-xs text-slate-700 dark:text-slate-300 border-b border-slate-50 dark:border-slate-800 last:border-0"
                                                    x-text="opt.name">
                                                </div>
                                            </template>
                                            
                                            <div x-show="search && search.length > 1 && !exactMatch" 
                                                x-on:click="create()"
                                                class="px-3 py-1.5 hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer text-[10px] font-black text-primary flex items-center gap-1.5 italic"
                                            >
                                                <x-ui.icon name="o-plus" class="size-3" />
                                                <span>{{ __('Buat') }}: "<span x-text="search" class="underline underline-offset-2"></span>"</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-0.5">
                                        <span class="text-[9px] text-slate-400 uppercase tracking-tight">{{ $item['category_name'] ?: __('Umum') }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    <x-ui.input type="number" wire:model.live.debounce.1000ms="formItems.{{ $index }}.quantity" class="text-center !py-1 font-mono text-xs w-20" min="1" />
                                </td>
                                <td class="px-3 py-2">
                                    <x-ui.input wire:model="formItems.{{ $index }}.unit" :placeholder="__('Pcs')" class="text-center !py-1 text-xs w-20" />
                                </td>
                                <td class="px-3 py-2">
                                    <x-ui.input type="number" wire:model.live.debounce.1300ms="formItems.{{ $index }}.amount" class="text-right !py-1 font-mono text-xs" min="0" />
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <div class="font-mono text-xs font-bold text-slate-900 dark:text-white whitespace-nowrap" wire:loading.remove wire:target="formItems.{{ $index }}.quantity, formItems.{{ $index }}.amount">
                                        Rp {{ number_format($item['total'], 0, ',', '.') }}
                                    </div>
                                    <div wire:loading wire:target="formItems.{{ $index }}.quantity, formItems.{{ $index }}.amount" class="flex justify-end">
                                        <span class="loading loading-spinner loading-xs text-primary"></span>
                                    </div>
                                </td>
                                <td class="px-2 py-2 text-center">
                                    <button wire:click="removeItemRow({{ $index }})" class="p-1 rounded-lg text-slate-300 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition-colors">
                                        <x-ui.icon name="o-trash" class="size-4" />
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-slate-100 dark:bg-slate-800/80 border-t-2 border-slate-200 dark:border-slate-700">
                            <tr>
                                <td colspan="4" class="px-4 py-3 text-right text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">{{ __('Total Estimasi Anggaran') }}</td>
                                <td class="px-3 py-3 text-right">
                                    <span class="font-mono text-base font-black text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                        Rp {{ number_format($this->totalAmount, 0, ',', '.') }}
                                    </span>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <x-ui.textarea wire:model="notes" :label="__('Catatan / Justifikasi')" :placeholder="__('Jelaskan tujuan pengajuan RAB ini secara singkat...')" rows="2" />
            
            <div class="flex flex-col md:flex-row justify-between items-center gap-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                <div class="flex gap-3">
                    @if($editing && ($editing->status === 'submitted' && (Auth::user()->isYayasan() || Auth::user()->isAdmin())))
                        <x-ui.button :label="__('Setujui Pengajuan')" class="btn-primary" wire:click="updateStatus({{ $editing->id }}, 'approved')" icon="o-check-circle" />
                        <x-ui.button :label="__('Tolak Pengajuan')" class="btn-ghost text-rose-600 hover:bg-rose-50" wire:click="updateStatus({{ $editing->id }}, 'rejected')" icon="o-x-circle" />
                    @endif

                    @if($editing && $editing->status === 'approved' && (Auth::user()->isYayasan() || Auth::user()->isAdmin()))
                        <x-ui.button :label="__('Konfirmasi Transfer Dana')" icon="o-banknotes" class="btn-primary" wire:click="updateStatus({{ $editing->id }}, 'transferred')" />
                    @endif
                </div>

                <div class="flex gap-3 w-full md:w-auto">
                    <x-ui.button :label="__('Tutup')" wire:click="$set('planModal', false)" class="md:grow-0 grow" />
                    @if(!$editing || $editing->status === 'draft' || $editing->status === 'rejected')
                        <x-ui.button :label="__('Simpan Draft')" class="btn-ghost border-slate-200 text-slate-600 md:grow-0 grow" wire:click="save('draft')" spinner="save" />
                        <x-ui.button :label="__('Simpan & Ajukan ke Yayasan')" class="btn-primary md:grow-0 grow shadow-lg shadow-primary/20" wire:click="save('submit')" spinner="save" />
                    @endif
                </div>
            </div>
        </div>
    </x-ui.modal>
</div>
