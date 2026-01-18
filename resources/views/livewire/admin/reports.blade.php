<?php

use App\Models\Transaction;
use App\Models\StudentBilling;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;

new class extends Component {
    public function with(): array
    {
        $totalRevenue = Transaction::sum('amount');
        $pendingBillings = StudentBilling::where('status', '!=', 'paid')
            ->sum(DB::raw('amount - paid_amount'));
        
        return [
            'totalRevenue' => $totalRevenue,
            'pendingBillings' => $pendingBillings,
        ];
    }
}; ?>

<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Laporan Keuangan</h1>
        <p class="text-zinc-600 dark:text-zinc-400">Ringkasan keuangan dan analitik PKBM.</p>
    </div>

    <!-- Financial Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="p-6 bg-white dark:bg-zinc-900 border rounded-xl shadow-sm">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2 bg-green-50 dark:bg-green-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <div>
                    <div class="text-sm text-zinc-500 uppercase tracking-wider font-semibold">Total Pendapatan</div>
                </div>
            </div>
            <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                Rp {{ number_format($totalRevenue, 0, ',', '.') }}
            </div>
        </div>

        <div class="p-6 bg-white dark:bg-zinc-900 border rounded-xl shadow-sm">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2 bg-orange-50 dark:bg-orange-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div>
                    <div class="text-sm text-zinc-500 uppercase tracking-wider font-semibold">Piutang Tagihan</div>
                </div>
            </div>
            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">
                Rp {{ number_format($pendingBillings, 0, ',', '.') }}
            </div>
        </div>
    </div>

    <!-- Additional Reports Content -->
    <div class="bg-white dark:bg-zinc-900 border rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold mb-4">Laporan Detail</h2>
        <p class="text-zinc-600 dark:text-zinc-400">
            Fitur laporan detail akan segera tersedia. Saat ini Anda dapat melihat ringkasan keuangan di atas.
        </p>
    </div>
</div>