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
use Livewire\Volt\Component;
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
        <div>
            <flux:heading size="xl" level="1">Transaksi Keuangan</flux:heading>
            <flux:subheading>Catat Pemasukan (Pembayaran Siswa) dan Pengeluaran (Realisasi RAB).</flux:subheading>
        </div>
        
        <div class="bg-blue-50 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 p-4 rounded-lg flex gap-3 text-sm items-start shadow-sm border border-blue-100 dark:border-blue-800">
            <flux:icon icon="information-circle" class="size-5 shrink-0 mt-0.5" />
            <div>
                <strong class="block mb-1">Tips Alur Keuangan:</strong>
                <ol class="list-decimal pl-4 space-y-1">
                    <li>Buat <strong class="font-medium">Kategori Biaya</strong> (SPP, Pendaftaran, dll).</li>
                    <li>Generate <strong class="font-medium">Tagihan Siswa</strong> untuk menagih biaya ke siswa (opsional, karena transaksi ini juga dapat membuat tagihan secara otomatis).</li>
                    <li>Berikan <strong class="font-medium">Potongan & Beasiswa</strong> jika diperlukan.</li>
                    <li>Gunakan halaman ini untuk <strong class="font-medium">Input Transaksi</strong> (menerima uang atau mengeluarkan uang).</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 space-y-6">
            <div class="border rounded-lg p-5 bg-white dark:bg-zinc-900 shadow-sm">
                <flux:heading level="2" size="lg" class="mb-4">Jenis Transaksi</flux:heading>
                
                <div class="flex gap-4 p-1 bg-zinc-100 dark:bg-zinc-800 rounded-lg">
                    <button 
                        wire:click="$set('type', 'income')"
                        class="flex-1 py-2 text-sm font-medium rounded-md transition-colors {{ $type === 'income' ? 'bg-white dark:bg-zinc-700 shadow flex items-center justify-center text-emerald-600 dark:text-emerald-400' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 flex items-center justify-center' }}"
                    >
                        <flux:icon icon="arrow-down-tray" variant="micro" class="mr-2" />
                        Pemasukan
                    </button>
                    <button 
                        wire:click="$set('type', 'expense')"
                        class="flex-1 py-2 text-sm font-medium rounded-md transition-colors {{ $type === 'expense' ? 'bg-white dark:bg-zinc-700 shadow flex items-center justify-center text-red-600 dark:text-red-400' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 flex items-center justify-center' }}"
                    >
                        <flux:icon icon="arrow-up-tray" variant="micro" class="mr-2" />
                        Pengeluaran
                    </button>
                </div>
            </div>

            @if($type === 'income')
                <div class="border rounded-lg p-5 bg-white dark:bg-zinc-900 shadow-sm">
                    <flux:heading level="2" size="lg" class="mb-4">Detail Pemasukan</flux:heading>
                    
                    <div class="space-y-4">
                        <flux:select wire:model.live="fee_category_id" label="Kategori Biaya" placeholder="Pilih Kategori">
                            @foreach($feeCategories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </flux:select>
                        
                        <div class="relative">
                            <flux:input 
                                wire:model.live.debounce.300ms="student_search" 
                                label="Cari Siswa"
                                placeholder="Ketik nama siswa..." 
                                icon="user" 
                            />
                            @if($student_id)
                                <button wire:click="$set('student_id', null); $set('student_search', ''); checkExistingBilling()" class="absolute right-2 top-[34px] p-1 text-zinc-400 hover:text-zinc-600">
                                    <flux:icon icon="x-mark" variant="micro" />
                                </button>
                            @endif
                        </div>

                        @if(count($students) > 0)
                            <div class="border rounded-md divide-y bg-white dark:bg-zinc-800 shadow-lg absolute z-10 w-full lg:w-[calc(33.333%-1rem)]">
                                @foreach($students as $student)
                                    <button 
                                        wire:click="selectStudent({{ $student->id }})"
                                        class="w-full text-left px-4 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition"
                                    >
                                        <div class="font-medium dark:text-white">{{ $student->name }}</div>
                                        <div class="text-xs text-zinc-500">{{ $student->email }}</div>
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        @if($student_id && $fee_category_id)
                            @if($selectedBilling)
                                <div class="mt-4 p-3 border border-emerald-200 bg-emerald-50 dark:bg-emerald-900/20 dark:border-emerald-800 rounded-lg">
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="text-sm font-medium text-emerald-800 dark:text-emerald-300">Tagihan Ditemukan</div>
                                        <flux:badge size="sm" :variant="$selectedBilling->status === 'partial' ? 'warning' : 'danger'">
                                            {{ strtoupper($selectedBilling->status) }}
                                        </flux:badge>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-zinc-600 dark:text-zinc-400">Total Biaya:</span>
                                        <span class="font-medium text-zinc-900 dark:text-zinc-100">Rp {{ number_format($selectedBilling->amount, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-zinc-600 dark:text-zinc-400">Telah Dibayar:</span>
                                        <span class="font-medium text-zinc-900 dark:text-zinc-100">Rp {{ number_format($selectedBilling->paid_amount, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm mt-1 pt-1 border-t border-emerald-200 dark:border-emerald-800 font-bold">
                                        <span class="text-emerald-800 dark:text-emerald-300">Sisa Tagihan:</span>
                                        <span class="text-emerald-800 dark:text-emerald-300">Rp {{ number_format($selectedBilling->amount - $selectedBilling->paid_amount, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            @else
                                <div class="mt-4 p-3 border border-zinc-200 bg-zinc-50 dark:bg-zinc-800 dark:border-zinc-700 rounded-lg">
                                    <div class="text-sm font-medium mb-1 text-zinc-800 dark:text-zinc-300 flex items-center gap-1.5">
                                        <flux:icon icon="information-circle" class="size-4 text-blue-500" />
                                        Tagihan Baru
                                    </div>
                                    <div class="text-xs text-zinc-500 leading-relaxed">
                                        Tidak ada tagihan tertunggak untuk kategori ini. Menyimpan transaksi akan otomatis membuatkan tagihan Lunas untuk siswa ini.
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            @endif

            @if($type === 'expense')
                <div class="border rounded-lg p-5 bg-white dark:bg-zinc-900 shadow-sm">
                    <flux:heading level="2" size="lg" class="mb-4">Detail RAB</flux:heading>
                    
                    <div class="space-y-4">
                        <flux:select wire:model.live="budget_plan_id" label="RAB Aktif" placeholder="Pilih RAB">
                            @foreach($activeBudgetPlans as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->title }} ({{ $plan->level->name ?? 'Semua' }})</option>
                            @endforeach
                        </flux:select>
                        
                        @if($budget_plan_id)
                            <flux:select wire:model.live="budget_plan_item_id" label="Item Anggaran" placeholder="Pilih Item">
                                @foreach($budgetItems as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }} (Anggaran: Rp {{ number_format($item->total, 0, ',', '.') }})</option>
                                @endforeach
                            </flux:select>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <div class="lg:col-span-2 space-y-6">
            @if(($type === 'income' && $student_id && $fee_category_id) || ($type === 'expense' && $budget_plan_id && $budget_plan_item_id))
                <div class="border rounded-lg p-6 bg-white dark:bg-zinc-900 shadow-sm {{ $type === 'income' ? 'border-emerald-500/30 bg-emerald-50/30 dark:bg-emerald-900/10' : 'border-red-500/30 bg-red-50/30 dark:bg-red-900/10' }}">
                    <flux:heading level="2" size="lg" class="mb-6">Form {{ $type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}</flux:heading>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <flux:input wire:model="pay_amount" type="number" label="Nominal (Rp)" icon="currency-dollar" />
                        <flux:select wire:model="payment_method" label="Metode Pembayaran">
                            <option value="cash">Tunai (Cash)</option>
                            <option value="transfer">Transfer Bank</option>
                            <option value="other">Lainnya</option>
                        </flux:select>
                        <flux:input wire:model="payment_date" type="date" label="Tanggal Transaksi" />
                        <flux:input wire:model="reference_number" label="Ref Transaksi (Optional)" placeholder="No. Slip/Ref" />
                    </div>

                    <div class="mt-6">
                        <flux:textarea wire:model="notes" label="Catatan Tambahan" rows="2" />
                    </div>

                    <div class="mt-8 flex justify-end">
                        <flux:button variant="primary" icon="check" wire:click="recordTransaction">
                            Simpan {{ $type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}
                        </flux:button>
                    </div>
                </div>
            @else
                <div class="border border-dashed rounded-lg p-12 text-center text-zinc-500 bg-zinc-50 dark:bg-zinc-900/50 flex flex-col items-center justify-center">
                    <flux:icon icon="document-text" class="size-12 mb-3 text-zinc-300 dark:text-zinc-600" />
                    <p>Lengkapi detail {{ $type === 'income' ? 'Pemasukan' : 'RAB Pengeluaran' }} di sebelah kiri untuk mengisi form transaksi.</p>
                </div>
            @endif

            <div class="border rounded-lg bg-white dark:bg-zinc-900 overflow-hidden shadow-sm">
                <div class="p-4 border-b bg-zinc-50 dark:bg-zinc-800">
                    <flux:heading level="2" size="md">Riwayat Transaksi Terbaru</flux:heading>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left border-collapse">
                        <thead class="bg-zinc-50 dark:bg-zinc-800">
                            <tr>
                                <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">Tanggal</th>
                                <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">Jenis</th>
                                <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">Keterangan</th>
                                <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse($recentTransactions as $tx)
                                <tr wire:key="tx-{{ $tx->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                    <td class="px-4 py-3 text-zinc-500 whitespace-nowrap">{{ $tx->payment_date->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3">
                                        @if($tx->type === 'income')
                                            <flux:badge size="sm" variant="success" icon="arrow-down-right">Pemasukan</flux:badge>
                                        @else
                                            <flux:badge size="sm" variant="danger" icon="arrow-up-right">Pengeluaran</flux:badge>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($tx->type === 'income')
                                            <div class="font-medium dark:text-zinc-200">{{ $tx->billing?->student?->name ?? 'Siswa Tidak Diketahui' }}</div>
                                            <div class="text-xs text-zinc-500">{{ $tx->billing?->feeCategory?->name ?? 'Tarif' }}</div>
                                        @else
                                            <div class="font-medium dark:text-zinc-200">{{ $tx->budgetItem?->name ?? 'RAB Item' }}</div>
                                            <div class="text-xs text-zinc-500">{{ $tx->budgetPlan?->title ?? 'RAB Terpadu' }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono font-medium {{ $tx->type === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $tx->type === 'income' ? '+' : '-' }} Rp {{ number_format($tx->amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-zinc-500">
                                        Belum ada transaksi tercatat.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
