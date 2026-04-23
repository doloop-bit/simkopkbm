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

new #[Layout('components.layouts.app')] class extends Component {
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

    @if(!auth()->user()->isYayasan())
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
    @endif

    @include('livewire.admin.financial.partials.recent-table')

    @include('livewire.admin.financial.partials.form-modal')
</div>

