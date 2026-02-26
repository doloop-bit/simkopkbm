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
use Illuminate\Support\Facades\DB;

new #[Layout('components.admin.layouts.app')] class extends Component {
    // General Form
    public string $type = 'income'; // 'income' or 'expense'
    public float $pay_amount = 0;
    public string $payment_method = 'cash';
    public string $payment_date = '';
    public string $reference_number = '';
    public string $notes = '';

    // Income Specific
    public ?int $fee_category_id = null;
    public ?int $student_id = null;
    public string $student_search = '';
    public ?StudentBilling $selectedBilling = null;

    // Expense Specific
    public ?int $budget_plan_id = null;
    public ?int $budget_plan_item_id = null;

    public function mount(): void
    {
        $this->payment_date = now()->format('Y-m-d');
        // Auto-select the first active budget plan if exists
        $activePlan = BudgetPlan::where('is_active', true)->first();
        if ($activePlan) {
            $this->budget_plan_id = $activePlan->id;
        }
    }

    public function updatedType() {
        $this->reset(['student_id', 'student_search', 'selectedBilling', 'fee_category_id', 'budget_plan_item_id', 'pay_amount', 'reference_number', 'notes']);
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
        $this->validate([
            'pay_amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string',
            'payment_date' => 'required|date',
        ]);

        if ($this->type === 'income') {
            $this->validate([
                'student_id' => 'required',
                'fee_category_id' => 'required',
            ]);

            DB::transaction(function () {
                $billing = $this->selectedBilling;
                
                // Auto create billing if it doesn't exist
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

                Transaction::create([
                    'type' => 'income',
                    'student_billing_id' => $billing->id,
                    'user_id' => auth()->id(),
                    'amount' => $this->pay_amount,
                    'payment_date' => $this->payment_date,
                    'payment_method' => $this->payment_method,
                    'reference_number' => $this->reference_number,
                    'notes' => $this->notes,
                ]);

                $newPaidAmount = $billing->paid_amount + $this->pay_amount;
                $status = 'paid';
                if ($newPaidAmount < $billing->amount) {
                    $status = 'partial';
                }

                $billing->update([
                    'paid_amount' => $newPaidAmount,
                    'status' => $status,
                ]);
            });

            \Flux::toast('Pemasukan berhasil dicatat.');
        } else {
            $this->validate([
                'budget_plan_id' => 'required',
                'budget_plan_item_id' => 'required',
            ]);

            Transaction::create([
                'type' => 'expense',
                'budget_plan_id' => $this->budget_plan_id,
                'budget_plan_item_id' => $this->budget_plan_item_id,
                'user_id' => auth()->id(),
                'amount' => $this->pay_amount,
                'payment_date' => $this->payment_date,
                'payment_method' => $this->payment_method,
                'reference_number' => $this->reference_number,
                'notes' => $this->notes,
            ]);

            \Flux::toast('Pengeluaran berhasil dicatat.');
        }

        $this->reset(['selectedBilling', 'student_id', 'student_search', 'fee_category_id', 'budget_plan_item_id', 'pay_amount', 'reference_number', 'notes']);
    }

    public function with(): array
    {
        $students = [];
        if (strlen($this->student_search) > 2 && !$this->student_id) {
            $students = User::where('role', 'siswa')
                ->where('name', 'like', "%{$this->student_search}%")
                ->limit(5)
                ->get();
        }

        $feeCategories = FeeCategory::all();
        $activeBudgetPlans = BudgetPlan::where('is_active', true)->get();
        
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

<div class="p-6">
    <div class="mb-6 space-y-4">
        <x-header title="Transaksi Keuangan" subtitle="Catat Pemasukan (Pembayaran Siswa) and Pengeluaran (Realisasi RAB)." separator />
        
        <x-alert title="Tips Alur Keuangan" icon="o-information-circle" class="bg-blue-50 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border-blue-100 dark:border-blue-800">
            <ol class="list-decimal pl-4 space-y-1 text-sm">
                <li>Buat <strong class="font-medium">Kategori Biaya</strong> (SPP, Pendaftaran, dll).</li>
                <li>Generate <strong class="font-medium">Tagihan Siswa</strong> untuk menagih biaya ke siswa (opsional, karena transaksi ini juga dapat membuat tagihan secara otomatis).</li>
                <li>Berikan <strong class="font-medium">Potongan & Beasiswa</strong> jika diperlukan.</li>
                <li>Gunakan halaman ini untuk <strong class="font-medium">Input Transaksi</strong> (menerima uang atau mengeluarkan uang).</li>
            </ol>
        </x-alert>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 space-y-6">
            <x-card title="Jenis Transaksi" shadow>
                <div class="flex gap-4 p-1 bg-base-200 rounded-lg">
                    <button 
                        wire:click="$set('type', 'income')"
                        class="flex-1 py-2 text-sm font-medium rounded-md transition-all {{ $type === 'income' ? 'bg-base-100 shadow flex items-center justify-center text-success' : 'text-base-content/50 hover:text-base-content flex items-center justify-center' }}"
                    >
                        <x-icon name="o-arrow-down-tray" class="mr-2 size-4" />
                        Pemasukan
                    </button>
                    <button 
                        wire:click="$set('type', 'expense')"
                        class="flex-1 py-2 text-sm font-medium rounded-md transition-all {{ $type === 'expense' ? 'bg-base-100 shadow flex items-center justify-center text-error' : 'text-base-content/50 hover:text-base-content flex items-center justify-center' }}"
                    >
                        <x-icon name="o-arrow-up-tray" class="mr-2 size-4" />
                        Pengeluaran
                    </button>
                </div>
            </x-card>

            @if($type === 'income')
                <x-card title="Detail Pemasukan" shadow>
                    <div class="space-y-4">
                        <x-select 
                            wire:model.live="fee_category_id" 
                            label="Kategori Biaya" 
                            placeholder="Pilih Kategori" 
                            :options="$feeCategories"
                        />
                        
                        <div class="relative">
                            <x-input 
                                wire:model.live.debounce.300ms="student_search" 
                                label="Cari Siswa"
                                placeholder="Ketik nama siswa..." 
                                icon="o-user" 
                                clearable
                                @clear="$wire.set('student_id', null); $wire.set('student_search', ''); $wire.checkExistingBilling()"
                            />
                        </div>

                        @if(count($students) > 0)
                            <div class="border rounded-md divide-y bg-base-100 shadow-lg absolute z-20 w-full lg:w-[calc(100%-3rem)] mt-1">
                                @foreach($students as $student)
                                    <button 
                                        wire:click="selectStudent({{ $student->id }})"
                                        class="w-full text-left px-4 py-2 hover:bg-base-200 transition"
                                    >
                                        <div class="font-medium">{{ $student->name }}</div>
                                        <div class="text-xs opacity-50">{{ $student->email }}</div>
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        @if($student_id && $fee_category_id)
                            @if($selectedBilling)
                                <div class="mt-4 p-3 border border-success/30 bg-success/10 rounded-lg">
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="text-sm font-medium text-success">Tagihan Ditemukan</div>
                                        <x-badge 
                                            :label="strtoupper($selectedBilling->status)" 
                                            class="{{ $selectedBilling->status === 'partial' ? 'badge-warning' : 'badge-error' }} badge-sm" 
                                        />
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="opacity-70">Total Biaya:</span>
                                        <span class="font-medium">Rp {{ number_format($selectedBilling->amount, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="opacity-70">Telah Dibayar:</span>
                                        <span class="font-medium">Rp {{ number_format($selectedBilling->paid_amount, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm mt-1 pt-1 border-t border-success/30 font-bold">
                                        <span class="text-success">Sisa Tagihan:</span>
                                        <span class="text-success">Rp {{ number_format($selectedBilling->amount - $selectedBilling->paid_amount, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            @else
                                <x-alert title="Tagihan Baru" icon="o-information-circle" class="bg-base-200 border-base-300">
                                    <div class="text-xs opacity-70 leading-relaxed">
                                        Tidak ada tagihan tertunggak untuk kategori ini. Menyimpan transaksi akan otomatis membuatkan tagihan Lunas untuk siswa ini.
                                    </div>
                                </x-alert>
                            @endif
                        @endif
                    </div>
                </x-card>
            @endif

            @if($type === 'expense')
                <x-card title="Detail RAB" shadow>
                    <div class="space-y-4">
                        <x-select 
                            wire:model.live="budget_plan_id" 
                            label="RAB Aktif" 
                            placeholder="Pilih RAB" 
                            :options="$activeBudgetPlans->map(fn($p) => ['id' => $p->id, 'name' => $p->title . ' (' . ($p->level->name ?? 'Semua') . ')'])"
                        />
                        
                        @if($budget_plan_id)
                            <x-select 
                                wire:model.live="budget_plan_item_id" 
                                label="Item Anggaran" 
                                placeholder="Pilih Item" 
                                :options="$budgetItems->map(fn($i) => ['id' => $i->id, 'name' => $i->name . ' (Anggaran: Rp ' . number_format($i->total, 0, ',', '.') . ')'])"
                            />
                        @endif
                    </div>
                </x-card>
            @endif
        </div>

        <div class="lg:col-span-2 space-y-6">
            @if(($type === 'income' && $student_id && $fee_category_id) || ($type === 'expense' && $budget_plan_id && $budget_plan_item_id))
                <x-card shadow class="{{ $type === 'income' ? 'border-success/30 bg-success/5' : 'border-error/30 bg-error/5' }}">
                    <x-header :title="'Form ' . ($type === 'income' ? 'Pemasukan' : 'Pengeluaran')" separator />
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-input wire:model="pay_amount" type="number" label="Nominal (Rp)" icon="o-currency-dollar" />
                        <x-select 
                            wire:model="payment_method" 
                            label="Metode Pembayaran" 
                            :options="[
                                ['id' => 'cash', 'name' => 'Tunai (Cash)'],
                                ['id' => 'transfer', 'name' => 'Transfer Bank'],
                                ['id' => 'other', 'name' => 'Lainnya']
                            ]"
                        />
                        <x-input wire:model="payment_date" type="date" label="Tanggal Transaksi" />
                        <x-input wire:model="reference_number" label="Ref Transaksi (Optional)" placeholder="No. Slip/Ref" />
                    </div>

                    <div class="mt-6">
                        <x-textarea wire:model="notes" label="Catatan Tambahan" rows="2" />
                    </div>

                    <x-slot:actions>
                        <x-button 
                            :label="'Simpan ' . ($type === 'income' ? 'Pemasukan' : 'Pengeluaran')" 
                            icon="o-check" 
                            class="btn-primary" 
                            wire:click="recordTransaction" 
                            spinner="recordTransaction"
                        />
                    </x-slot:actions>
                </x-card>
            @else
                <div class="border border-dashed border-base-300 rounded-lg p-12 text-center opacity-50 bg-base-200/50 flex flex-col items-center justify-center h-full min-h-[300px]">
                    <x-icon name="o-document-text" class="size-12 mb-3" />
                    <p>Lengkapi detail {{ $type === 'income' ? 'Pemasukan' : 'RAB Pengeluaran' }} di sebelah kiri untuk mengisi form transaksi.</p>
                </div>
            @endif

            <x-card title="Riwayat Transaksi Terbaru" separator shadow>
                <x-table :headers="[
                    ['key' => 'payment_date', 'label' => 'Tanggal'],
                    ['key' => 'type_label', 'label' => 'Jenis'],
                    ['key' => 'description', 'label' => 'Keterangan'],
                    ['key' => 'amount', 'label' => 'Nominal', 'class' => 'text-right']
                ]" :rows="$recentTransactions" no-hover>
                    @scope('cell_payment_date', $tx)
                        <span class="opacity-70">{{ $tx->payment_date->format('d/m/Y') }}</span>
                    @endscope

                    @scope('cell_type_label', $tx)
                        @if($tx->type === 'income')
                            <x-badge label="Pemasukan" class="badge-success badge-sm" icon="o-arrow-down-right" />
                        @else
                            <x-badge label="Pengeluaran" class="badge-error badge-sm" icon="o-arrow-up-right" />
                        @endif
                    @endscope

                    @scope('cell_description', $tx)
                        @if($tx->type === 'income')
                            <div class="font-medium">{{ $tx->billing?->student?->name ?? 'Siswa Tidak Diketahui' }}</div>
                            <div class="text-xs opacity-50">{{ $tx->billing?->feeCategory?->name ?? 'Tarif' }}</div>
                        @else
                            <div class="font-medium">{{ $tx->budgetItem?->name ?? 'RAB Item' }}</div>
                            <div class="text-xs opacity-50">{{ $tx->budgetPlan?->title ?? 'RAB Terpadu' }}</div>
                        @endif
                    @endscope

                    @scope('cell_amount', $tx)
                        <div class="font-mono font-bold font-medium {{ $tx->type === 'income' ? 'text-success' : 'text-error' }}">
                            {{ $tx->type === 'income' ? '+' : '-' }} Rp {{ number_format($tx->amount, 0, ',', '.') }}
                        </div>
                    @endscope
                </x-table>
                
                @if($recentTransactions->isEmpty())
                    <div class="py-8 text-center opacity-50 text-sm">
                        Belum ada transaksi tercatat.
                    </div>
                @endif
            </x-card>
        </div>
    </div>
</div>
