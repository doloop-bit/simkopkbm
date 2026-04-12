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
        $user = auth()->user();
        $isTreasurer = $user->role === 'bendahara';
        
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();
        
        // Basic Stats
        $totalStudents = User::where('role', 'siswa')->count();
        $totalTeachers = User::where('role', 'guru')->count();
        $totalStaff = User::whereNotIn('role', ['siswa', 'guru'])->count();
        $totalClassrooms = Classroom::count();
        
        // Financial Stats
        $incomeMonth = Transaction::where('type', 'income')
            ->whereBetween('payment_date', [$start, $end])
            ->sum('amount');
            
        $expenseMonth = Transaction::where('type', 'expense')
            ->whereBetween('payment_date', [$start, $end])
            ->sum('amount');
            
        $pendingBillings = StudentBilling::where('status', '!=', 'paid')
            ->sum(\Illuminate\Support\Facades\DB::raw('amount - paid_amount'));

        // Recent Activity
        $recentTransactions = Transaction::with(['billing.student', 'billing.feeCategory', 'budgetPlan', 'budgetItem'])
            ->latest()
            ->limit(10)
            ->get();

        $recentAttendance = [];
        if (!$isTreasurer) {
            $recentAttendance = Attendance::with(['classroom'])
                ->withCount('items')
                ->latest()
                ->limit(5)
                ->get();
        }

        $activeBudgetPlans = [];
        if ($isTreasurer || $user->isAdmin()) {
            $activeBudgetPlans = \App\Models\BudgetPlan::where('is_active', true)
                ->withCount('items')
                ->latest()
                ->limit(5)
                ->get();
        }

        return [
            'isTreasurer' => $isTreasurer,
            'stats' => [
                'students' => $totalStudents,
                'teachers' => $totalTeachers,
                'staff' => $totalStaff,
                'classrooms' => $totalClassrooms,
                'income_month' => $incomeMonth,
                'expense_month' => $expenseMonth,
                'balance_month' => $incomeMonth - $expenseMonth,
                'pending_billings' => $pendingBillings,
            ],
            'recentTransactions' => $recentTransactions,
            'recentAttendance' => $recentAttendance,
            'activeBudgetPlans' => $activeBudgetPlans,
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
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        @if($isTreasurer)
            <x-ui.stat
                :title="__('Pemasukan (Bulan Ini)')"
                :value="'Rp ' . number_format($stats['income_month'], 0, ',', '.')"
                icon="o-arrow-trending-up"
                color="text-emerald-600 dark:text-emerald-400"
            />
            <x-ui.stat
                :title="__('Pengeluaran (Bulan Ini)')"
                :value="'Rp ' . number_format($stats['expense_month'], 0, ',', '.')"
                icon="o-arrow-trending-down"
                color="text-rose-600 dark:text-rose-400"
            />
            <x-ui.stat
                :title="__('Saldo (Bulan Ini)')"
                :value="'Rp ' . number_format($stats['balance_month'], 0, ',', '.')"
                icon="o-banknotes"
                color="{{ $stats['balance_month'] >= 0 ? 'text-blue-600' : 'text-rose-600' }}"
            />
            <x-ui.stat
                :title="__('Piutang Tagihan')"
                :value="'Rp ' . number_format($stats['pending_billings'], 0, ',', '.')"
                icon="o-document-minus"
                color="text-amber-600 dark:text-amber-400"
            />
        @else
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
                :title="__('Total Staf')"
                :value="$stats['staff']"
                icon="o-briefcase"
                color="text-amber-600 dark:text-amber-400"
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
        @endif
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Transactions -->
        <x-ui.card :title="__('Aktivitas Keuangan Terbaru')" separator shadow>
            <x-slot:actions>
                <x-ui.button :label="__('Lihat Semua')" :link="route('financial.transactions')" wire:navigate ghost />
            </x-slot:actions>
            
            <div class="divide-y divide-slate-100 dark:divide-slate-800/50">
                @forelse($recentTransactions as $tx)
                    <div class="py-4 flex justify-between items-center transition-all hover:bg-slate-50/50 dark:hover:bg-slate-800/30 -mx-4 px-4 rounded-xl group">
                        <x-ui.list-item no-separator no-hover class="!p-0 w-full">
                            <x-slot:avatar>
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold shadow-inner group-hover:scale-105 transition-transform {{ $tx->type === 'income' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                                    @if($tx->type === 'income')
                                        <x-ui.icon name="o-plus" class="size-5" />
                                    @else
                                        <x-ui.icon name="o-minus" class="size-5" />
                                    @endif
                                </div>
                            </x-slot:avatar>
                            <x-slot:value>
                                <span class="text-xs uppercase tracking-widest font-black text-slate-400 block -mb-0.5">{{ $tx->type === 'income' ? __('Pemasukan') : __('Pengeluaran') }}</span>
                                <div class="font-bold text-slate-900 dark:text-white">
                                    @if($tx->type === 'income')
                                        {{ $tx->billing?->student?->name ?? 'System' }}
                                    @else
                                        {{ $tx->budgetItem?->name ?? 'Operasional' }}
                                    @endif
                                </div>
                            </x-slot:value>
                            <x-slot:sub-value>
                                <div class="flex items-center gap-1.5 grayscale opacity-70">
                                    <x-ui.icon name="o-calendar" class="size-3" />
                                    {{ $tx->payment_date->format('d M Y') }}
                                    <span class="mx-1">•</span>
                                    {{ Str::limit($tx->notes ?? $tx->payment_method, 30) }}
                                </div>
                            </x-slot:sub-value>
                        </x-ui.list-item>
                        <div class="text-right">
                            <div class="font-bold font-mono {{ $tx->type === 'income' ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $tx->type === 'income' ? '+' : '-' }} Rp {{ number_format($tx->amount, 0, ',', '.') }}
                            </div>
                            <div class="text-[10px] uppercase font-black text-slate-400 tracking-tighter">{{ $tx->payment_date->diffForHumans() }}</div>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-slate-500 italic">Belum ada transaksi diinput.</div>
                @endforelse
            </div>
        </x-ui.card>

        @if($isTreasurer)
            <!-- Budget Plans for Treasurer -->
            <x-ui.card :title="__('Rencana Anggaran (RAB) Aktif')" separator shadow>
                <x-slot:actions>
                    <x-ui.button :label="__('Lihat Semua')" :link="route('financial.budget-plans')" wire:navigate ghost />
                </x-slot:actions>
                
                <div class="divide-y divide-slate-100 dark:divide-slate-800/50">
                    @forelse($activeBudgetPlans as $plan)
                        <div class="py-4 flex justify-between items-center transition-all hover:bg-slate-50/50 dark:hover:bg-slate-800/30 -mx-4 px-4 rounded-xl group">
                            <div class="flex gap-4 items-center">
                                <div class="p-2.5 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-xl shadow-sm group-hover:bg-blue-100 dark:group-hover:bg-blue-900/40 transition-colors">
                                    <x-ui.icon name="o-document-currency-dollar" class="size-6" />
                                </div>
                                <div>
                                    <div class="font-bold text-slate-900 dark:text-white">{{ $plan->title }}</div>
                                    <div class="text-xs font-medium text-slate-500 dark:text-slate-400">
                                        {{ $plan->items_count }} Item Anggaran
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs font-bold px-2 py-0.5 rounded-md bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800 uppercase tracking-widest">
                                    {{ __('Aktif') }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-12 text-center text-slate-500 italic">Belum ada RAB aktif.</div>
                    @endforelse
                </div>
            </x-ui.card>
        @else
            <!-- Recent Attendance for Admin/Guru -->
            <x-ui.card :title="__('Input Presensi Terakhir')" separator shadow>
                <x-slot:actions>
                    <x-ui.button :label="__('Lihat Semua')" :link="route('academic.attendance')" wire:navigate ghost />
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
        @endif
    </div>
    </div>
</div>
