<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Transaction;
use App\Models\StudentBilling;
use App\Models\Classroom;
use App\Models\Attendance;
use Livewire\Component;

new class extends Component {
    public function with(): array
    {
        $totalStudents = User::where('role', 'siswa')->count();
        $totalTeachers = User::where('role', 'guru')->count();
        $totalClassrooms = Classroom::count();
        
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();
        
        $incomeMonth = Transaction::whereBetween('payment_date', [$start, $end])
            ->sum('amount');
            
        $pendingBillings = StudentBilling::where('status', '!=', 'paid')
            ->sum(\Illuminate\Support\Facades\DB::raw('amount - paid_amount'));

        $recentTransactions = Transaction::with(['billing.student', 'billing.feeCategory'])
            ->latest()
            ->limit(5)
            ->get();

        $recentAttendance = Attendance::with(['classroom'])
            ->withCount('items')
            ->latest()
            ->limit(5)
            ->get();

        return [
            'stats' => [
                'students' => $totalStudents,
                'teachers' => $totalTeachers,
                'classrooms' => $totalClassrooms,
                'income_month' => $incomeMonth,
                'pending_billings' => $pendingBillings,
            ],
            'recentTransactions' => $recentTransactions,
            'recentAttendance' => $recentAttendance,
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <x-ui.header :title="__('Selamat Datang, :name', ['name' => auth()->user()->name])" :subtitle="__('Ringkasan aktivitas PKBM hari ini.')">
        <x-slot:actions>
            <div class="text-sm font-semibold text-slate-500 bg-slate-100 dark:bg-slate-800 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700">
                {{ now()->translatedFormat('l, d F Y') }}
            </div>
        </x-slot:actions>
    </x-ui.header>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-ui.stat
            :title="__('Total Siswa')"
            :value="$stats['students']"
            icon="o-users"
            color="text-blue-600 dark:text-blue-400"
        />

        <x-ui.stat
            :title="__('Total Guru')"
            :value="$stats['teachers']"
            icon="o-academic-cap"
            color="text-indigo-600 dark:text-indigo-400"
        />

        <x-ui.stat
            :title="__('Pendapatan (Bulan Ini)')"
            :value="'Rp ' . number_format($stats['income_month'] / 1000, 0) . 'k'"
            icon="o-banknotes"
            color="text-emerald-600 dark:text-emerald-400"
        />

        <x-ui.stat
            :title="__('Piutang Tagihan')"
            :value="'Rp ' . number_format($stats['pending_billings'] / 1000000, 1) . 'M'"
            icon="o-document-minus"
            color="text-amber-600 dark:text-amber-400"
        />
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Transactions -->
        <x-ui.card :title="__('Transaksi Terakhir')" separator shadow>
            <x-slot:actions>
                <x-ui.button :label="__('Lihat Semua')" :link="route('financial.transactions')" wire:navigate ghost sm />
            </x-slot:actions>
            
            <div class="divide-y divide-slate-100 dark:divide-slate-800/50">
                @forelse($recentTransactions as $tx)
                    <div class="py-4 flex justify-between items-center transition-all hover:bg-slate-50/50 dark:hover:bg-slate-800/30 -mx-4 px-4 rounded-xl group">
                        <x-ui.list-item no-separator no-hover class="!p-0 w-full">
                            <x-slot:avatar>
                                <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center font-bold text-slate-500 dark:text-slate-400 shadow-inner group-hover:scale-105 transition-transform">
                                    {{ substr($tx->billing?->student?->name ?? '?', 0, 1) }}
                                </div>
                            </x-slot:avatar>
                            <x-slot:value>
                                {{ $tx->billing?->student?->name ?? 'Siswa Dihapus' }}
                            </x-slot:value>
                            <x-slot:sub-value>
                                {{ $tx->billing?->feeCategory?->name ?? 'Kategori Dihapus' }} - {{ $tx->payment_method }}
                            </x-slot:sub-value>
                        </x-ui.list-item>
                        <div class="text-right">
                            <div class="font-bold text-emerald-600 dark:text-emerald-400 font-mono">+ Rp {{ number_format($tx->amount, 0, ',', '.') }}</div>
                            <div class="text-[10px] uppercase font-bold text-slate-400">{{ $tx->payment_date->diffForHumans() }}</div>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-slate-500 italic">Belum ada transaksi masuk.</div>
                @endforelse
            </div>
        </x-ui.card>

        <!-- Recent Attendance -->
        <x-ui.card :title="__('Input Presensi Terakhir')" separator shadow>
            <x-slot:actions>
                <x-ui.button :label="__('Lihat Semua')" :link="route('academic.attendance')" wire:navigate ghost sm />
            </x-slot:actions>
            
            <div class="divide-y divide-slate-100 dark:divide-slate-800/50">
                @forelse($recentAttendance as $att)
                    <div class="py-4 flex justify-between items-center transition-all hover:bg-slate-50/50 dark:hover:bg-slate-800/30 -mx-4 px-4 rounded-xl group">
                        <div class="flex gap-4 items-center">
                            <div class="p-2.5 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-xl shadow-sm group-hover:bg-blue-100 dark:group-hover:bg-blue-900/40 transition-colors">
                                <x-ui.icon name="o-clipboard-document-check" class="size-6" />
                            </div>
                            <div>
                                <div class="font-bold text-slate-900 dark:text-white">Kelas {{ $att->classroom->name }}</div>
                                <div class="text-xs font-medium text-slate-500 dark:text-slate-400">
                                    {{ $att->subject?->name ?? 'Harian' }} • {{ $att->date->format('d M Y') }}
                                </div>
                            </div>
                        </div>
                        <div class="text-xs font-bold px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                            {{ $att->items_count ?? $att->items()->count() }} SISWA
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-slate-500 italic">Belum ada data presensi.</div>
                @endforelse
            </div>
        </x-ui.card>
    </div>
</div>
