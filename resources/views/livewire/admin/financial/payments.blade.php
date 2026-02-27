<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\StudentBilling;
use App\Models\Transaction;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Mary\Traits\Toast;

new #[Layout('components.admin.layouts.app')] class extends Component {
    use Toast;

    public ?int $student_id = null;
    public string $search = '';
    
    public ?StudentBilling $selectedBilling = null;
    public float $pay_amount = 0;
    public string $payment_method = 'cash';
    public string $payment_date = '';
    public string $reference_number = '';
    public string $notes = '';

    public function mount(): void
    {
        $this->payment_date = now()->format('Y-m-d');
    }

    public function selectStudent(int $id): void
    {
        $this->student_id = $id;
        $this->search = User::find($id)->name;
    }

    public function selectBilling(StudentBilling $billing): void
    {
        $this->selectedBilling = $billing;
        $this->pay_amount = (float) ($billing->amount - $billing->paid_amount);
    }

    public function recordPayment(): void
    {
        $this->validate([
            'pay_amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string',
            'payment_date' => 'required|date',
        ]);

        if (!$this->selectedBilling) return;

        DB::transaction(function () {
            Transaction::create([
                'student_billing_id' => $this->selectedBilling->id,
                'user_id' => auth()->id(),
                'amount' => $this->pay_amount,
                'payment_date' => $this->payment_date,
                'payment_method' => $this->payment_method,
                'reference_number' => $this->reference_number,
                'notes' => $this->notes,
            ]);

            $newPaidAmount = $this->selectedBilling->paid_amount + $this->pay_amount;
            $status = 'paid';
            if ($newPaidAmount < $this->selectedBilling->amount) {
                $status = 'partial';
            }

            $this->selectedBilling->update([
                'paid_amount' => $newPaidAmount,
                'status' => $status,
            ]);
        });

        $this->success('Pembayaran berhasil dicatat.');
        $this->reset(['selectedBilling', 'pay_amount', 'reference_number', 'notes', 'student_id', 'search']);
    }

    public function with(): array
    {
        $students = [];
        if (strlen($this->search) > 2 && !$this->student_id) {
            $students = User::where('role', 'siswa')
                ->where('name', 'like', "%{$this->search}%")
                ->limit(5)
                ->get();
        }

        $billings = [];
        if ($this->student_id) {
            $billings = StudentBilling::with('feeCategory')
                ->where('student_id', $this->student_id)
                ->where('status', '!=', 'paid')
                ->get();
        }

        $recentTransactions = Transaction::with(['billing.student', 'billing.feeCategory'])
            ->latest()
            ->limit(10)
            ->get();

        return [
            'students' => $students,
            'billings' => $billings,
            'recentTransactions' => $recentTransactions,
        ];
    }
}; ?>

<div class="p-6">
    <x-header title="Transaksi Pembayaran" subtitle="Catat pembayaran biaya sekolah dari siswa." separator />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 space-y-6">
            <div class="border rounded-xl p-4 bg-white dark:bg-zinc-900 shadow-sm border-zinc-200 dark:border-zinc-700">
                <h2 class="text-lg font-bold mb-4">Cari Siswa</h2>
                <div class="relative">
                    <x-input 
                        wire:model.live.debounce.300ms="search" 
                        placeholder="Ketik nama siswa..." 
                        icon="o-user" 
                    />
                    @if($student_id || $search)
                        <button wire:click="$set('student_id', null); $set('search', '')" class="absolute right-2 top-11 -translate-y-1/2 p-2 text-zinc-400 hover:text-zinc-600">
                            <x-icon name="o-x-mark" class="w-4 h-4" />
                        </button>
                    @endif
                </div>

                @if(count($students) > 0)
                    <div class="mt-2 border rounded-xl divide-y bg-white dark:bg-zinc-800 shadow-lg absolute z-10 w-[calc(100%-2rem)] border-zinc-200 dark:border-zinc-700">
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
            </div>

            @if($student_id)
                <div class="border rounded-xl p-4 bg-white dark:bg-zinc-900 shadow-sm border-zinc-200 dark:border-zinc-700">
                    <h2 class="text-lg font-bold mb-4">Tagihan Belum Lunas</h2>
                    <div class="space-y-3 text-left">
                        @forelse($billings as $billing)
                            <div class="p-3 border rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-800 cursor-pointer transition border-zinc-200 dark:border-zinc-700 {{ $selectedBilling?->id === $billing->id ? 'bg-primary/10 border-primary/30' : 'bg-white dark:bg-zinc-900' }}" wire:click="selectBilling({{ $billing->id }})">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <div class="font-bold dark:text-white">{{ $billing->feeCategory?->name ?? 'Kategori Dihapus' }}</div>
                                        <div class="text-xs text-zinc-500">{{ $billing->month ?? 'Sekali Bayar' }}</div>
                                    </div>
                                    <x-badge :value="strtoupper($billing->status)" class="{{ $billing->status === 'partial' ? 'badge-warning' : 'badge-error' }} badge-sm" />
                                </div>
                                <div class="mt-2 flex justify-between items-end">
                                    <div class="text-xs text-zinc-500">Sisa:</div>
                                    <div class="font-mono text-zinc-900 dark:text-white font-bold">Rp {{ number_format($billing->amount - $billing->paid_amount, 0, ',', '.') }}</div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-zinc-500 text-center py-4">Tidak ada tagihan tertunggak.</p>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>

        <div class="lg:col-span-2 space-y-6">
            @if($selectedBilling)
                <div class="border rounded-xl p-6 shadow-sm border-primary/20 bg-primary/5 dark:bg-primary/10">
                    <h2 class="text-lg font-bold mb-6">Form Pembayaran</h2>
                    
                    <div class="grid grid-cols-2 gap-6 mb-6 text-left">
                        <div class="space-y-1">
                            <div class="text-xs text-zinc-500 uppercase tracking-wider">Siswa</div>
                            <div class="font-bold text-lg dark:text-white">{{ $selectedBilling->student?->name ?? 'Siswa Dihapus' }}</div>
                        </div>
                        <div class="space-y-1">
                            <div class="text-xs text-zinc-500 uppercase tracking-wider">Kategori</div>
                            <div class="font-bold text-lg dark:text-white">{{ $selectedBilling->feeCategory?->name ?? 'Kategori Dihapus' }}</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-left">
                        <x-input wire:model="pay_amount" type="number" label="Nominal Pembayaran" icon="o-banknotes" />
                        <x-select wire:model="payment_method" label="Metode Pembayaran" :options="[['id' => 'cash', 'name' => 'Tunai (Cash)'], ['id' => 'transfer', 'name' => 'Transfer Bank'], ['id' => 'other', 'name' => 'Lainnya']]" />
                        <x-input wire:model="payment_date" type="date" label="Tanggal Pembayaran" />
                        <x-input wire:model="reference_number" label="Ref Transaksi (Optional)" placeholder="No. Slip/Ref" />
                    </div>

                    <div class="mt-6 text-left">
                        <x-textarea wire:model="notes" label="Catatan" rows="2" />
                    </div>

                    <div class="mt-8 flex justify-end">
                        <x-button label="Simpan Pembayaran" icon="o-check" class="btn-primary" wire:click="recordPayment" spinner="recordPayment" />
                    </div>
                </div>
            @endif

            <div class="border rounded-xl bg-white dark:bg-zinc-900 overflow-hidden shadow-sm border-zinc-200 dark:border-zinc-700">
                <div class="p-4 border-b bg-zinc-50 dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700">
                    <h2 class="text-md font-bold">Transaksi Terakhir</h2>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th class="bg-base-200">Tanggal</th>
                            <th class="bg-base-200">Siswa</th>
                            <th class="bg-base-200">Biaya</th>
                            <th class="bg-base-200 text-right">Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentTransactions as $tx)
                            <tr class="hover" wire:key="tx-{{ $tx->id }}">
                                <td class="text-zinc-500 whitespace-nowrap">{{ $tx->payment_date->format('d/m/Y') }}</td>
                                <td class="font-medium dark:text-white">{{ $tx->billing?->student?->name ?? 'Siswa Dihapus' }}</td>
                                <td class="text-zinc-600 dark:text-zinc-400">{{ $tx->billing?->feeCategory?->name ?? 'Kategori Dihapus' }}</td>
                                <td class="text-right font-mono text-success font-bold">
                                    Rp {{ number_format($tx->amount, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
