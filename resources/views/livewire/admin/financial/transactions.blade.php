<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\StudentBilling;
use App\Models\Transaction;
use App\Models\FeeCategory;
use App\Models\BudgetPlan;
use App\Models\BudgetPlanItem;
use App\Models\AcademicYear;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

new #[Layout('components.admin.layouts.app')] class extends Component {
    use WithFileUploads;
    // General Form
    public string $type = 'income'; // 'income' or 'expense'
    public float $pay_amount = 0;
    public float $adjustment_amount = 0;
    public string $payment_method = 'cash';
    public string $payment_date = '';
    public string $reference_number = '';
    public string $notes = '';
    public $attachments = [];
    public bool $recordModal = false;

    // Income Specific
    public ?int $fee_category_id = null;
    public bool $is_global = false;
    public ?int $student_id = null;
    public string $student_search = '';
    public ?StudentBilling $selectedBilling = null;

    // Expense Specific
    public ?int $budget_plan_id = null;
    public ?int $budget_plan_item_id = null;

    // Management
    public ?int $editingTransactionId = null;

    public function mount(): void
    {
        $this->payment_date = now()->format('Y-m-d');
        // Auto-select the first active budget plan if exists
        $activePlan = BudgetPlan::where('is_active', true)->first();
        if ($activePlan) {
            $this->budget_plan_id = $activePlan->id;
        }
    }

    public function closeModal(): void
    {
        $this->recordModal = false;
        $this->editingTransactionId = null;
        $this->reset(['is_global', 'student_id', 'student_search', 'selectedBilling', 'fee_category_id', 'budget_plan_id', 'budget_plan_item_id', 'pay_amount', 'adjustment_amount', 'reference_number', 'notes', 'attachments']);
    }

    public function switchType(string $type) {
        $this->type = $type;
        $this->closeModal();
        
        if ($type === 'expense') {
            $activePlan = BudgetPlan::where('is_active', true)->first();
            if ($activePlan) {
                $this->budget_plan_id = $activePlan->id;
            }
        }

        $this->recordModal = true;
    }

    public function selectStudent(int $id): void
    {
        $this->student_id = $id;
        $this->student_search = User::find($id)->name;
        $this->checkExistingBilling();
    }

    public function updatedFeeCategoryId()
    {
        $this->checkExistingBilling();
        // If no billing exists, but category has default amount, set it
        if (!$this->selectedBilling && $this->fee_category_id) {
            $cat = FeeCategory::find($this->fee_category_id);
            if ($cat) {
                $this->pay_amount = (float) $cat->default_amount;
            }
        }
    }

    public function checkExistingBilling()
    {
        if ($this->student_id && $this->fee_category_id) {
            $billing = StudentBilling::where('student_id', $this->student_id)
                ->where('fee_category_id', $this->fee_category_id)
                ->where('status', '!=', 'paid')
                ->first();
            
            if ($billing) {
                $this->selectedBilling = $billing;
                $this->pay_amount = (float) ($billing->amount - $billing->paid_amount);
            } else {
                $this->selectedBilling = null;
            }
        } else {
            $this->selectedBilling = null;
        }
    }

    public function recordTransaction(): void
    {
        // 1. Basic Validation (Shared)
        $rules = [
            'pay_amount' => 'required|numeric|min:0',
            'adjustment_amount' => 'nullable|numeric',
            'payment_method' => 'required|string',
            'payment_date' => 'required|date',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:2048|mimes:jpg,jpeg,png,pdf',
        ];

        if ($this->type === 'income') {
            $rules['fee_category_id'] = 'required';
            if (!$this->is_global) {
                $rules['student_id'] = 'required';
            }
        } else {
            $rules['budget_plan_id'] = 'required';
            $rules['budget_plan_item_id'] = 'required';
        }

        if (!$this->editingTransactionId && $this->type !== 'income') {
            $rules['attachments'] = 'required|array|min:1';
        }

        $this->validate($rules);

        try {
            DB::transaction(function () {
                $attachmentPaths = [];
                
                if ($this->editingTransactionId) {
                    $tx = Transaction::findOrFail($this->editingTransactionId);
                    $attachmentPaths = $tx->attachment ?? [];
                    
                    // If reversing income, reset billing first
                    if ($tx->type === 'income' && $tx->student_billing_id) {
                        $oldBilling = StudentBilling::find($tx->student_billing_id);
                        if ($oldBilling) {
                            $oldBilling->paid_amount -= ($tx->amount + ($tx->adjustment_amount ?? 0));
                            $oldBilling->status = $oldBilling->paid_amount <= 0 ? 'unpaid' : ($oldBilling->paid_amount < $oldBilling->amount ? 'partial' : 'paid');
                            $oldBilling->save();
                        }
                    }
                }

                if ($this->attachments) {
                    foreach ($this->attachments as $file) {
                        if ($file) {
                            $attachmentPaths[] = $file->store('transaction-proofs', 'public');
                        }
                    }
                }

                if ($this->type === 'income') {
                    if ($this->is_global) {
                        $txData = [
                            'type' => 'income',
                            'fee_category_id' => $this->fee_category_id,
                            'student_billing_id' => null,
                            'user_id' => auth()->id(),
                            'amount' => $this->pay_amount,
                            'adjustment_amount' => $this->adjustment_amount,
                            'payment_date' => $this->payment_date,
                            'payment_method' => $this->payment_method,
                            'reference_number' => $this->reference_number,
                            'notes' => $this->notes,
                            'attachment' => $attachmentPaths,
                        ];

                        if ($this->editingTransactionId) {
                            Transaction::where('id', $this->editingTransactionId)->update($txData);
                        } else {
                            Transaction::create($txData);
                        }

                        session()->flash('success', $this->editingTransactionId ? __('Transaksi diperbarui.') : __('Pemasukan dicatat.'));
                    } else {
                        $billing = $this->selectedBilling;
                        
                        if (!$billing) {
                            $activeYear = AcademicYear::where('is_active', true)->first();
                            $billing = StudentBilling::create([
                                'student_id' => $this->student_id,
                                'fee_category_id' => $this->fee_category_id,
                                'academic_year_id' => $activeYear ? $activeYear->id : null,
                                'amount' => $this->pay_amount,
                                'paid_amount' => 0,
                                'status' => 'unpaid'
                            ]);
                        }

                        $txData = [
                            'type' => 'income',
                            'student_billing_id' => $billing->id,
                            'fee_category_id' => $this->fee_category_id,
                            'user_id' => auth()->id(),
                            'amount' => $this->pay_amount,
                            'adjustment_amount' => $this->adjustment_amount,
                            'payment_date' => $this->payment_date,
                            'payment_method' => $this->payment_method,
                            'reference_number' => $this->reference_number,
                            'notes' => $this->notes,
                            'attachment' => $attachmentPaths,
                        ];

                        if ($this->editingTransactionId) {
                            Transaction::where('id', $this->editingTransactionId)->update($txData);
                        } else {
                            Transaction::create($txData);
                        }

                        $newPaidAmount = $billing->paid_amount + $this->pay_amount + $this->adjustment_amount;
                        $status = $newPaidAmount >= $billing->amount ? 'paid' : ($newPaidAmount > 0 ? 'partial' : 'unpaid');

                        $billing->update([
                            'paid_amount' => $newPaidAmount,
                            'status' => $status,
                        ]);

                        session()->flash('success', $this->editingTransactionId ? __('Transaksi diperbarui.') : __('Pemasukan dicatat.'));
                    }
                } else {
                    $txData = [
                        'type' => 'expense',
                        'budget_plan_id' => $this->budget_plan_id,
                        'budget_plan_item_id' => $this->budget_plan_item_id,
                        'user_id' => auth()->id(),
                        'amount' => $this->pay_amount,
                        'adjustment_amount' => $this->adjustment_amount,
                        'payment_date' => $this->payment_date,
                        'payment_method' => $this->payment_method,
                        'reference_number' => $this->reference_number,
                        'notes' => $this->notes,
                        'attachment' => $attachmentPaths,
                    ];

                    if ($this->editingTransactionId) {
                        Transaction::where('id', $this->editingTransactionId)->update($txData);
                    } else {
                        Transaction::create($txData);
                    }

                    session()->flash('success', $this->editingTransactionId ? __('Transaksi diperbarui.') : __('Pengeluaran dicatat.'));
                }
            });

            $this->closeModal();
            
        } catch (\Exception $e) {
            session()->flash('error', __('Terjadi kesalahan: ') . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('Transaction Error: ' . $e->getMessage());
        }
    }

    public function deleteTransaction(int $id): void
    {
        try {
            DB::transaction(function () use ($id) {
                $tx = Transaction::findOrFail($id);
                
                if ($tx->type === 'income' && $tx->student_billing_id) {
                    $billing = StudentBilling::find($tx->student_billing_id);
                    if ($billing) {
                        $newAmount = max(0, $billing->paid_amount - ($tx->amount + ($tx->adjustment_amount ?? 0)));
                        $status = $newAmount <= 0 ? 'unpaid' : ($newAmount < $billing->amount ? 'partial' : 'paid');
                        $billing->update(['paid_amount' => $newAmount, 'status' => $status]);
                    }
                }

                if ($tx->attachment) {
                    foreach ((array)$tx->attachment as $path) {
                        Storage::disk('public')->delete($path);
                    }
                }

                $tx->delete();
            });
            session()->flash('success', __('Transaksi berhasil dihapus.'));
        } catch (\Exception $e) {
            session()->flash('error', __('Gagal menghapus: ') . $e->getMessage());
        }
    }

    public function editTransaction(int $id): void
    {
        $tx = Transaction::findOrFail($id);
        $this->editingTransactionId = $id;
        $this->type = $tx->type;
        $this->pay_amount = (float) $tx->amount;
        $this->adjustment_amount = (float) ($tx->adjustment_amount ?? 0);
        $this->payment_date = $tx->payment_date->format('Y-m-d');
        $this->payment_method = $tx->payment_method;
        $this->reference_number = $tx->reference_number ?? '';
        $this->notes = $tx->notes ?? '';
        $this->attachments = []; // New attachments only
        
        if ($tx->type === 'income') {
            $this->is_global = empty($tx->billing);
            $this->student_id = $tx->billing?->student_id;
            $this->student_search = $tx->billing?->student?->name ?? '';
            $this->fee_category_id = $tx->fee_category_id ?? $tx->billing?->fee_category_id;
            $this->selectedBilling = $tx->billing;
        } else {
            $this->budget_plan_id = $tx->budget_plan_id;
            $this->budget_plan_item_id = $tx->budget_plan_item_id;
        }

        $this->recordModal = true;
    }

    public function with(): array
    {
        $user = auth()->user();
        
        $students = [];
        if (strlen($this->student_search) > 2 && !$this->student_id) {
            $studentQuery = User::where('role', 'siswa')
                ->where('name', 'like', "%{$this->student_search}%");
                
            if ($user->role === 'bendahara' && $user->managed_level_id) {
                // Limit student search to bendahara's level
                $studentQuery->whereHas('studentProfile.classroom', function ($q) use ($user) {
                    $q->where('level_id', $user->managed_level_id);
                });
            }
                
            $students = $studentQuery->limit(5)->get();
        }

        $feeQuery = FeeCategory::query();
        $budgetQuery = BudgetPlan::where('is_active', true);

        if ($user->role === 'bendahara' && $user->managed_level_id) {
            $feeQuery->where(function($q) use ($user) {
                $q->where('level_id', $user->managed_level_id)->orWhereNull('level_id');
            });
            $budgetQuery->where(function($q) use ($user) {
                $q->where('level_id', $user->managed_level_id)->orWhereNull('level_id');
            });
        }

        $feeCategories = $feeQuery->get();
        $activeBudgetPlans = $budgetQuery->get();
        
        $budgetItems = [];
        if ($this->budget_plan_id) {
            $budgetItems = BudgetPlanItem::where('budget_plan_id', $this->budget_plan_id)->get();
        }

        $recentTransactions = Transaction::with(['billing.student', 'billing.feeCategory', 'budgetPlan', 'budgetItem'])
            ->latest()
            ->limit(15)
            ->get();

        return [
            'students' => $students,
            'feeCategories' => $feeCategories,
            'activeBudgetPlans' => $activeBudgetPlans,
            'budgetItems' => $budgetItems,
            'recentTransactions' => $recentTransactions,
        ];
    }
}; ?>

<div class="p-6 space-y-6 text-slate-900 dark:text-white pb-24 md:pb-6">
    @if (session('success'))
        <x-ui.alert :title="__('Sukses')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100" dismissible>
            {{ session('success') }}
        </x-ui.alert>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <button 
            type="button"
            wire:click="switchType('income')"
            wire:loading.attr="disabled"
            class="group relative overflow-hidden p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all text-left"
        >
            <div class="flex items-center gap-4">
                <div class="size-12 bg-emerald-50 dark:bg-emerald-950/30 rounded-xl flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform">
                    <div wire:loading wire:target="switchType('income')">
                        <x-ui.icon name="o-arrow-path" class="size-6 animate-spin" />
                    </div>
                    <div wire:loading.remove wire:target="switchType('income')">
                        <x-ui.icon name="o-arrow-down-tray" class="size-6" />
                    </div>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900 dark:text-white uppercase tracking-tighter">{{ __('Catat Pemasukan') }}</h3>
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest">{{ __('Pembayaran SPP, dll.') }}</p>
                </div>
            </div>
        </button>

        <button 
            type="button"
            wire:click="switchType('expense')"
            wire:loading.attr="disabled"
            class="group relative overflow-hidden p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all text-left"
        >
            <div class="flex items-center gap-4">
                <div class="size-12 bg-rose-50 dark:bg-rose-950/30 rounded-xl flex items-center justify-center text-rose-600 group-hover:scale-110 transition-transform">
                    <div wire:loading wire:target="switchType('expense')">
                        <x-ui.icon name="o-arrow-path" class="size-6 animate-spin" />
                    </div>
                    <div wire:loading.remove wire:target="switchType('expense')">
                        <x-ui.icon name="o-arrow-up-tray" class="size-6" />
                    </div>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900 dark:text-white uppercase tracking-tighter">{{ __('Catat Pengeluaran') }}</h3>
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest">{{ __('Realisasi RAB, dll.') }}</p>
                </div>
            </div>
        </button>
    </div>

    <div class="space-y-4">
            <x-ui.card shadow padding="false">
                <div class="p-5 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex items-center justify-between">
                    <div class="text-xs font-bold uppercase text-slate-500 tracking-wider">{{ __('Riwayat Transaksi Terbaru') }}</div>
                    <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider bg-white dark:bg-slate-800 px-3 py-1 rounded-full ring-1 ring-slate-100 dark:ring-slate-700 shadow-sm">{{ __('Real-time Update') }}</div>
                </div>

                <x-ui.table 
                    :headers="[
                    ['key' => 'payment_date', 'label' => __('Tanggal')],
                    ['key' => 'type_label', 'label' => __('Jenis')],
                    ['key' => 'description', 'label' => __('Keterangan')],
                    ['key' => 'amount', 'label' => __('Nominal'), 'class' => 'text-right'],
                    ['key' => 'actions', 'label' => '', 'class' => 'w-10']
                ]" 
                :rows="$recentTransactions"
            >
                    @scope('cell_payment_date', $tx)
                        <span class="text-[11px] font-mono font-bold text-slate-400 uppercase">{{ $tx->payment_date->format('d M Y') }}</span>
                    @endscope

                    @scope('cell_type_label', $tx)
                        @if($tx->type === 'income')
                            <x-ui.badge :label="__('In')" class="bg-emerald-100 text-emerald-700 border-none text-[10px] font-bold px-2 py-0.5 tracking-wide" />
                        @else
                            <x-ui.badge :label="__('Out')" class="bg-rose-100 text-rose-700 border-none text-[10px] font-bold px-2 py-0.5 tracking-wide" />
                        @endif
                    @endscope

                    @scope('cell_description', $tx)
                        <div class="flex flex-col">
                            @if($tx->type === 'income')
                                @if($tx->billing)
                                    <span class="font-semibold text-slate-900 dark:text-white">{{ $tx->billing->student?->name ?? __('Siswa') }}</span>
                                    <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ $tx->billing->feeCategory?->name ?? __('Tarif') }}</span>
                                @else
                                    <span class="font-semibold text-slate-900 dark:text-white">{{ __('Pemasukan Global/Saldo Awal') }}</span>
                                    <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ $tx->feeCategory?->name ?? __('Kategori') }}</span>
                                @endif
                            @else
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-slate-900 dark:text-white">{{ $tx->budgetItem?->name ?? __('RAB Item') }}</span>
                                    @if($tx->attachment)
                                        <div class="flex gap-1">
                                            @foreach((array)$tx->attachment as $file)
                                                <a href="{{ Storage::url($file) }}" target="_blank" class="p-1 bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-100 transition-colors" title="{{ __('Lihat Bukti') }}">
                                                    <x-ui.icon name="o-paper-clip" class="size-3" />
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 truncate max-w-[150px]">{{ $tx->budgetPlan?->title ?? __('RAB Terpadu') }}</span>
                            @endif
                        </div>
                    @endscope

                @scope('cell_amount', $tx)
                    <div class="font-mono text-xs font-bold {{ $tx->type === 'income' ? 'text-emerald-600' : 'text-rose-600' }}">
                        {{ $tx->type === 'income' ? '+' : '-' }} Rp {{ number_format($tx->amount, 0, ',', '.') }}
                    </div>
                @endscope

                @scope('cell_actions', $tx)
                    <div class="flex items-center justify-end gap-2">
                        <button wire:click="editTransaction({{ $tx->id }})" class="text-slate-400 hover:text-blue-500 transition-colors">
                            <x-ui.icon name="o-pencil" class="size-4" />
                        </button>
                        <button 
                            wire:click="deleteTransaction({{ $tx->id }})" 
                            wire:confirm="{{ __('Hapus transaksi ini? Tindakan ini akan menyesuaikan tagihan terkait.') }}"
                            class="text-slate-400 hover:text-rose-500 transition-colors"
                        >
                            <x-ui.icon name="o-trash" class="size-4" />
                        </button>
                    </div>
                @endscope
                </x-ui.table>
                
                @if($recentTransactions->isEmpty())
                    <div class="py-12 text-center text-slate-400 italic text-sm">
                        {{ __('Belum ada transaksi yang tercatat hari ini.') }}
                    </div>
                @endif
            </x-ui.card>
        </div>

    {{-- MODAL FORM --}}
    <form wire:submit="recordTransaction">
        <x-ui.modal wire:model="recordModal" :title="($editingTransactionId ? __('Koreksi Transaksi') : ($type === 'income' ? __('Catat Pemasukan Baru') : __('Catat Pengeluaran Baru')))" maxWidth="max-w-4xl">
            <div class="space-y-8">
                @if ($errors->any())
                    <x-ui.alert :title="__('Perhatian')" icon="o-exclamation-triangle" class="bg-rose-50 text-rose-800 border-rose-100">
                        <ul class="text-xs font-semibold list-disc pl-5 mt-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </x-ui.alert>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-start">
                    {{-- SELECTION COLUMN --}}
                    <div class="space-y-6">
                        <div class="text-[10px] font-black uppercase text-slate-400 tracking-widest">{{ __('Destinasi Keuangan') }}</div>
                        
                        @if($type === 'income')
                            <x-ui.checkbox wire:model.live="is_global" :label="__('Saldo Awal / Pemasukan Global (Tanpa Siswa)')" class="text-[10px] font-bold text-slate-700 dark:text-slate-300 mb-4" />
                            
                            <x-ui.select 
                                wire:model.live="fee_category_id" 
                                :label="__('Kategori Biaya')" 
                                :placeholder="__('Pilih Kategori')" 
                                :options="$feeCategories"
                            />
                            
                            @if(!$is_global)
                                <div class="relative">
                                <x-ui.input 
                                    wire:model.live.debounce.300ms="student_search" 
                                    :label="__('Cari Nama Siswa')"
                                    :placeholder="__('Ketik minimal 3 huruf...')" 
                                    icon="o-magnifying-glass" 
                                    clearable
                                    @clear="$wire.set('student_id', null); $wire.set('student_search', ''); $wire.checkExistingBilling()"
                                />

                                @if(count($students) > 0)
                                    <div class="absolute z-50 w-full mt-2 bg-white dark:bg-slate-900 rounded-2xl shadow-2xl ring-1 ring-slate-200 dark:ring-slate-800 overflow-hidden divide-y divide-slate-50 dark:divide-slate-800">
                                        @foreach($students as $student)
                                            <button 
                                                type="button"
                                                wire:click="selectStudent({{ $student->id }})"
                                                class="w-full text-left px-5 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors group"
                                            >
                                                <div class="font-bold text-slate-900 dark:text-white group-hover:text-primary transition-colors text-xs">{{ $student->name }}</div>
                                                <div class="text-[9px] text-slate-400 font-mono tracking-tighter">{{ $student->email }}</div>
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            @if($student_id && $fee_category_id && $selectedBilling)
                                <div class="p-4 bg-emerald-50 dark:bg-emerald-950/20 rounded-xl border border-emerald-100 dark:border-emerald-900/50 space-y-3">
                                    <div class="flex justify-between items-center text-[10px] font-bold uppercase text-emerald-700 tracking-wider">
                                        <span>{{ __('Tagihan Aktif') }}</span>
                                        <x-ui.badge :label="strtoupper($selectedBilling->status)" class="text-[8px]" />
                                    </div>
                                    <div class="flex justify-between text-xs font-black">
                                        <span class="text-slate-500 uppercase tracking-tighter">{{ __('Sisa Tagihan:') }}</span>
                                        <span class="text-emerald-600 dark:text-emerald-300 font-mono text-base">Rp {{ number_format($selectedBilling->amount - $selectedBilling->paid_amount, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            @endif
                            @endif
                        @else
                            <x-ui.select 
                                wire:model.live="budget_plan_id" 
                                :label="__('RAB Aktif')" 
                                :placeholder="__('Pilih Dokumen RAB')" 
                                :options="$activeBudgetPlans->map(fn($p) => ['id' => $p->id, 'name' => $p->title . ' (' . ($p->level?->name ?? __('Semua Tingkat')) . ')'])"
                            />
                            
                            @if($budget_plan_id)
                                <x-ui.select 
                                    wire:model.live="budget_plan_item_id" 
                                    :label="__('Item Anggaran')" 
                                    :placeholder="__('Pilih Pos Anggaran')" 
                                    :options="$budgetItems->map(fn($i) => ['id' => $i->id, 'name' => $i->name . ' (Anggaran: Rp ' . number_format($i->total, 0, ',', '.') . ')'])"
                                />
                            @endif
                        @endif
                    </div>

                    {{-- TRANSACTION DETAIL COLUMN --}}
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <x-ui.input wire:model="pay_amount" type="number" :label="__('Nominal Bayar (Rp)')" icon="o-banknotes" required />
                            <x-ui.input wire:model="adjustment_amount" type="number" :label="__('Adjusment (+/-)')" icon="o-adjustments-horizontal" :placeholder="__('Contoh: -500')" />
                        </div>
                        
                        <x-ui.select 
                            wire:model="payment_method" 
                            :label="__('Metode Pembayaran')" 
                            :options="[
                                ['id' => 'cash', 'name' => __('Tunai (Cash)')],
                                ['id' => 'transfer', 'name' => __('Transfer Bank')],
                                ['id' => 'other', 'name' => __('Lainnya')]
                            ]"
                            required
                        />
                        <x-ui.input wire:model="payment_date" type="date" :label="__('Tanggal Transaksi')" required />
                        <x-ui.textarea wire:model="notes" :label="__('Catatan')" rows="1" :placeholder="__('Detail tambahan...')" />
                    </div>
                </div>
            </div>

            {{-- COMPACT ATTACHMENTS --}}
            <div class="border-t border-slate-100 dark:border-slate-800 pt-6 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="text-[10px] font-black uppercase text-slate-400 tracking-widest">{{ __('Lampiran Bukti / Kwitansi') }}</div>
                    <div wire:loading wire:target="attachments" class="text-[10px] font-bold text-emerald-600 animate-pulse">{{ __('Mengunggah...') }}</div>
                </div>
                
                <div class="flex flex-wrap items-center gap-3">
                    @foreach($attachments as $index => $file)
                        <div class="relative size-16 rounded-xl overflow-hidden bg-slate-100 border border-slate-200 group">
                            @php
                                $isImage = false;
                                try {
                                    $isImage = in_array($file->getClientOriginalExtension(), ['jpg', 'jpeg', 'png']);
                                } catch(\Exception $e) {}
                            @endphp
                            @if($isImage)
                                <img src="{{ $file->temporaryUrl() }}" class="w-full h-full object-cover">
                            @else
                                <div class="flex items-center justify-center h-full bg-slate-50">
                                    <x-ui.icon name="o-document" class="size-6 text-slate-400" />
                                </div>
                            @endif
                            <button type="button" @click="$wire.set('attachments.{{ $index }}', null)" class="absolute inset-0 bg-rose-500/80 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <x-ui.icon name="o-trash" class="size-4" />
                            </button>
                        </div>
                    @endforeach

                    @if(count($attachments) < 5)
                        <label class="size-16 rounded-xl border-2 border-dashed border-slate-200 dark:border-slate-800 flex flex-col items-center justify-center cursor-pointer hover:bg-slate-50 transition-colors group">
                            <x-ui.icon name="o-camera" class="size-5 text-slate-400 group-hover:text-primary transition-colors" />
                            <input wire:model="attachments" type="file" class="hidden" accept="image/*,.pdf" multiple />
                        </label>
                    @endif

                    @if($editingTransactionId)
                        <div class="text-[9px] font-bold text-slate-400 italic max-w-xs">{{ __('File baru akan ditambahkan ke lampiran yang sudah ada.') }}</div>
                    @endif
                </div>
                @error('attachments') <span class="text-xs text-rose-500 font-bold block">{{ $message }}</span> @enderror
            </div>

            <x-slot name="actions">
                <x-ui.button :label="__('Batal')" @click="show = false; $wire.closeModal()" class="btn-ghost" />
                <x-ui.button 
                    :label="$editingTransactionId ? __('Simpan Koreksi') : __('Simpan Transaksi')" 
                    type="submit"
                    icon="o-check-circle" 
                    class="{{ $type === 'income' ? 'btn-primary' : 'bg-rose-600 hover:bg-rose-700 text-white border-rose-700' }}" 
                    spinner="recordTransaction"
                />
            </x-slot>
        </x-ui.modal>
    </form>
</div>

