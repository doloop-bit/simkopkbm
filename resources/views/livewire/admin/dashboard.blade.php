<?php

declare(strict_types=1);

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\BudgetPlan;
use App\Models\Classroom;
use App\Models\Level;
use App\Models\StudentBilling;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component
{
    public function with(): array
    {
        $user = auth()->user();
        $isTreasurer = $user->isTreasurer();
        $isYayasan = $user->isYayasan();
        $isKepsek = $user->isHeadmaster();
        $hasFinancialAccess = $user->isAdmin() || $isTreasurer || $isYayasan || $isKepsek;

        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        $totalStudents = User::where('role', 'siswa')->count();
        $totalTeachers = User::where('role', 'guru')->count();
        $totalStaff = User::whereNotIn('role', ['siswa', 'guru'])->count();
        $totalClassrooms = Classroom::count();

        $incomeMonth = Transaction::where('type', 'income')
            ->whereBetween('payment_date', [$start, $end])
            ->sum('amount');

        $expenseMonth = Transaction::where('type', 'expense')
            ->whereBetween('payment_date', [$start, $end])
            ->sum('amount');

        $pendingBillings = StudentBilling::where('status', '!=', 'paid')->sum(DB::raw('amount - paid_amount'));

        $recentTransactions = Transaction::with(['billing.student', 'billing.feeCategory', 'budgetPlan', 'budgetItem'])
            ->latest()
            ->limit(10)
            ->get();

        $recentAttendance = [];
        if (! $isTreasurer) {
            $recentAttendance = Attendance::with(['classroom'])
                ->withCount('items')
                ->latest()
                ->limit(5)
                ->get();
        }

        $activeBudgetPlans = [];
        if ($isTreasurer || $user->isAdmin()) {
            $activeBudgetPlans = BudgetPlan::where('is_active', true)->withCount('items')->latest()->limit(5)->get();
        }

        $chartData = [];
        $showDebtors = false;
        if ($hasFinancialAccess) {
            $chartData = $this->getFinancialChartData($user);
            $showDebtors = $user->isAdmin() || $isTreasurer;
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
            'chartData' => $chartData,
            'showDebtors' => $showDebtors,
            'hasFinancialAccess' => $hasFinancialAccess,
        ];
    }

    private function getFinancialChartData(User $user): array
    {
        $levelId = $user->isTreasurer() ? $user->managed_level_id : null;

        return [
            'cashFlow' => $this->getCashFlowData($levelId),
            'incomeComposition' => $this->getIncomeCompositionData($levelId),
            'expenseComposition' => $this->getExpenseCompositionData($levelId),
            'collectionRate' => $this->getCollectionRateData($levelId),
            'budgetRealization' => $this->getBudgetRealizationData($levelId),
            'rabTrend' => $this->getRabTrendData($levelId),
            'topDebtors' => ($user->isAdmin() || $user->isTreasurer()) ? $this->getTopDebtorsData($levelId) : [],
        ];
    }

    /**
     * ① Cash Flow: Last 6 months income vs expense
     */
    private function getCashFlowData(?int $levelId): array
    {
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $months->push(now()->subMonths($i));
        }

        $labels = $months->map(fn (Carbon $d) => $d->translatedFormat('M Y'))->toArray();
        $income = [];
        $expense = [];

        foreach ($months as $month) {
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $incomeQuery = Transaction::where('type', 'income')
                ->whereBetween('payment_date', [$start, $end]);
            $expenseQuery = Transaction::where('type', 'expense')
                ->whereBetween('payment_date', [$start, $end]);

            if ($levelId) {
                $this->applyLevelFilter($incomeQuery, $levelId);
                $this->applyLevelFilter($expenseQuery, $levelId);
            }

            $income[] = (float) $incomeQuery->sum('amount');
            $expense[] = (float) $expenseQuery->sum('amount');
        }

        return compact('labels', 'income', 'expense');
    }

    /**
     * ② Income composition by fee category (current month)
     */
    private function getIncomeCompositionData(?int $levelId): array
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        $query = Transaction::where('type', 'income')
            ->whereBetween('payment_date', [$start, $end])
            ->select('fee_category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('fee_category_id')
            ->with('feeCategory');

        if ($levelId) {
            $this->applyLevelFilter($query, $levelId);
        }

        $results = $query->get();

        $palette = ['#10b981', '#3b82f6', '#f59e0b', '#8b5cf6', '#06b6d4', '#ec4899', '#14b8a6'];
        $colors = [];
        foreach ($results as $i => $r) {
            $colors[] = $palette[$i % count($palette)];
        }

        return [
            'labels' => $results->map(fn ($r) => $r->feeCategory?->name ?? 'Lainnya')->values()->all(),
            'values' => $results->pluck('total')->map(fn ($v) => (float) $v)->values()->all(),
            'colors' => $colors,
        ];
    }

    /**
     * ③ Expense composition by budget plan item (current month)
     */
    private function getExpenseCompositionData(?int $levelId): array
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        $query = Transaction::where('type', 'expense')
            ->whereBetween('payment_date', [$start, $end])
            ->select('budget_plan_item_id', DB::raw('SUM(amount) as total'))
            ->groupBy('budget_plan_item_id')
            ->with('budgetItem');

        if ($levelId) {
            $this->applyLevelFilter($query, $levelId);
        }

        $results = $query->get();

        $palette = ['#f43f5e', '#f97316', '#ec4899', '#ef4444', '#d946ef', '#e11d48', '#fb923c'];
        $colors = [];
        foreach ($results as $i => $r) {
            $colors[] = $palette[$i % count($palette)];
        }

        return [
            'labels' => $results->map(fn ($r) => $r->budgetItem?->name ?? 'Lainnya')->values()->all(),
            'values' => $results->pluck('total')->map(fn ($v) => (float) $v)->values()->all(),
            'colors' => $colors,
        ];
    }

    /**
     * ④ Billing collection rate per level
     */
    private function getCollectionRateData(?int $levelId): array
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (! $activeYear) {
            return [];
        }

        $levelsQuery = Level::query();
        if ($levelId) {
            $levelsQuery->where('id', $levelId);
        }

        $levels = $levelsQuery->get();
        $result = [];

        foreach ($levels as $level) {
            $classroomIds = Classroom::where('level_id', $level->id)->pluck('id');

            $studentIds = DB::table('student_profiles')
                ->whereIn('classroom_id', $classroomIds)
                ->pluck('id');

            $userIds = DB::table('profiles')
                ->where('profileable_type', 'App\Models\StudentProfile')
                ->whereIn('profileable_id', $studentIds)
                ->pluck('user_id');

            $billingData = StudentBilling::whereIn('student_id', $userIds)
                ->where('academic_year_id', $activeYear->id)
                ->selectRaw('COALESCE(SUM(amount), 0) as total, COALESCE(SUM(paid_amount), 0) as paid')
                ->first();

            $result[] = [
                'name' => $level->name,
                'total' => (float) ($billingData->total ?? 0),
                'paid' => (float) ($billingData->paid ?? 0),
            ];
        }

        return $result;
    }

    /**
     * ⑤ Budget realization: planned vs actual per active budget plan
     */
    private function getBudgetRealizationData(?int $levelId): array
    {
        $query = BudgetPlan::where('is_active', true);
        if ($levelId) {
            $query->where('level_id', $levelId);
        }

        $plans = $query->get();

        if ($plans->isEmpty()) {
            return ['labels' => [], 'planned' => [], 'realized' => []];
        }

        $labels = [];
        $planned = [];
        $realized = [];

        foreach ($plans as $plan) {
            $labels[] = $plan->title;
            $planned[] = (float) $plan->total_amount;
            $realized[] = (float) Transaction::where('type', 'expense')
                ->where('budget_plan_id', $plan->id)
                ->sum('amount');
        }

        return compact('labels', 'planned', 'realized');
    }

    /**
     * ⑥ RAB yearly trend: cumulative monthly spending vs ceiling
     */
    private function getRabTrendData(?int $levelId): array
    {
        $query = BudgetPlan::where('is_active', true);
        if ($levelId) {
            $query->where('level_id', $levelId);
        }

        $activePlan = $query->first();
        if (! $activePlan) {
            return ['months' => [], 'cumulative' => [], 'ceiling' => []];
        }

        $year = now()->year;
        $currentMonth = now()->month;
        $monthLabels = [];
        $monthlySpending = [];

        for ($m = 1; $m <= 12; $m++) {
            $monthLabels[] = Carbon::createFromDate($year, $m, 1)->translatedFormat('M');

            if ($m <= $currentMonth) {
                $start = Carbon::createFromDate($year, $m, 1)->startOfDay();
                $end = $start->copy()->endOfMonth()->endOfDay();

                $monthlySpending[] = (float) Transaction::where('type', 'expense')
                    ->where('budget_plan_id', $activePlan->id)
                    ->whereBetween('payment_date', [$start, $end])
                    ->sum('amount');
            } else {
                $monthlySpending[] = null;
            }
        }

        $cumulative = [];
        $runningTotal = 0;
        foreach ($monthlySpending as $val) {
            if ($val !== null) {
                $runningTotal += $val;
                $cumulative[] = $runningTotal;
            } else {
                $cumulative[] = null;
            }
        }

        $ceiling = array_fill(0, 12, (float) $activePlan->total_amount);

        return [
            'months' => $monthLabels,
            'cumulative' => $cumulative,
            'ceiling' => $ceiling,
        ];
    }

    /**
     * ⑦ Top debtors: students with highest unpaid balance
     */
    private function getTopDebtorsData(?int $levelId): array
    {
        $query = StudentBilling::where('status', '!=', 'paid')
            ->select('student_id', DB::raw('SUM(amount - paid_amount) as total_unpaid'), DB::raw('COUNT(*) as billing_count'))
            ->groupBy('student_id')
            ->orderByDesc('total_unpaid')
            ->limit(10);

        $results = $query->get();

        $debtors = [];
        foreach ($results as $row) {
            $student = User::find($row->student_id);
            if (! $student) {
                continue;
            }

            $levelName = '-';
            $studentProfile = $student->studentProfile;
            if ($studentProfile) {
                $classroom = Classroom::find($studentProfile->classroom_id);
                if ($classroom) {
                    if ($levelId && $classroom->level_id !== $levelId) {
                        continue;
                    }
                    $level = Level::find($classroom->level_id);
                    $levelName = $level?->name ?? '-';
                }
            }

            $debtors[] = [
                'name' => $student->name,
                'level' => $levelName,
                'unpaid' => (float) $row->total_unpaid,
                'billing_count' => (int) $row->billing_count,
            ];
        }

        return $debtors;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    private function applyLevelFilter($query, int $levelId): void
    {
        $query->where(function ($q) use ($levelId) {
            $q->whereHas('billing.student.studentProfile.classroom', function ($sq) use ($levelId) {
                $sq->where('level_id', $levelId);
            })
                ->orWhereHas('feeCategory', function ($fq) use ($levelId) {
                    $fq->where('level_id', $levelId);
                })
                ->orWhereHas('budgetPlan', function ($bq) use ($levelId) {
                    $bq->where('level_id', $levelId);
                });
        });
    }
}; ?>

<div class="space-y-6">
    {{-- Header --}}
    <x-ui.header :title="__('Selamat Datang, :name', ['name' => auth()->user()->name])" :subtitle="__('Ringkasan aktivitas PKBM hari ini.')">
        <x-slot:actions>
            <div
                class="text-sm font-semibold text-slate-500 bg-slate-100 dark:bg-slate-800 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700">
                {{ now()->translatedFormat('l, d F Y') }}
            </div>
        </x-slot:actions>
    </x-ui.header>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        @if ($isTreasurer)
            <x-ui.stat :title="__('Pemasukan (Bulan Ini)')" :value="'Rp ' . number_format($stats['income_month'], 0, ',', '.')" icon="o-arrow-trending-up"
                color="text-emerald-600 dark:text-emerald-400" />
            <x-ui.stat :title="__('Pengeluaran (Bulan Ini)')" :value="'Rp ' . number_format($stats['expense_month'], 0, ',', '.')" icon="o-arrow-trending-down"
                color="text-rose-600 dark:text-rose-400" />
            <x-ui.stat :title="__('Saldo (Bulan Ini)')" :value="'Rp ' . number_format($stats['balance_month'], 0, ',', '.')" icon="o-banknotes"
                color="{{ $stats['balance_month'] >= 0 ? 'text-blue-600' : 'text-rose-600' }}" />
            <x-ui.stat :title="__('Piutang Tagihan')" :value="'Rp ' . number_format($stats['pending_billings'], 0, ',', '.')" icon="o-document-minus"
                color="text-amber-600 dark:text-amber-400" />
        @else
            <x-ui.stat :title="__('Total Siswa')" :value="$stats['students']" icon="o-users" color="text-blue-600 dark:text-blue-400" />

            <x-ui.stat :title="__('Total Guru')" :value="$stats['teachers']" icon="o-academic-cap"
                color="text-indigo-600 dark:text-indigo-400" />

            <x-ui.stat :title="__('Total Staf')" :value="$stats['staff']" icon="o-briefcase"
                color="text-amber-600 dark:text-amber-400" />

            <x-ui.stat :title="__('Pendapatan (Bulan Ini)')" :value="'Rp ' . number_format($stats['income_month'] / 1000, 0) . 'k'" icon="o-banknotes"
                color="text-emerald-600 dark:text-emerald-400" />

            <x-ui.stat :title="__('Piutang Tagihan')" :value="'Rp ' . number_format($stats['pending_billings'] / 1000000, 1) . 'M'" icon="o-document-minus"
                color="text-amber-600 dark:text-amber-400" />
        @endif
    </div>

    {{-- Financial Dashboard Charts --}}
    @if($hasFinancialAccess)
        @include('livewire.admin.partials.financial-dashboard')
    @endif

    {{-- Main Content --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Recent Transactions --}}
        <x-ui.card :title="__('Aktivitas Keuangan Terbaru')" separator shadow>
            <x-slot:actions>
                <x-ui.button :label="__('Lihat Semua')" :link="route('financial.transactions')" wire:navigate ghost />
            </x-slot:actions>

            <div class="divide-y divide-slate-100 dark:divide-slate-800/50">
                @forelse($recentTransactions as $tx)
                    <div
                        class="py-4 flex justify-between items-center transition-all hover:bg-slate-50/50 dark:hover:bg-slate-800/30 -mx-4 px-4 rounded-xl group">
                        <x-ui.list-item no-separator no-hover class="p-0! w-full">
                            <x-slot:avatar>
                                <div
                                    class="w-10 h-10 rounded-xl flex items-center justify-center font-bold shadow-inner group-hover:scale-105 transition-transform {{ $tx->type === 'income' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                                    @if ($tx->type === 'income')
                                        <x-ui.icon name="o-plus" class="size-5" />
                                    @else
                                        <x-ui.icon name="o-minus" class="size-5" />
                                    @endif
                                </div>
                            </x-slot:avatar>
                            <x-slot:value>
                                <span
                                    class="text-xs uppercase tracking-widest font-black text-slate-400 block -mb-0.5">{{ $tx->type === 'income' ? __('Pemasukan') : __('Pengeluaran') }}</span>
                                <div class="font-bold text-slate-900 dark:text-white">
                                    @if ($tx->type === 'income')
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
                            <div
                                class="font-bold font-mono {{ $tx->type === 'income' ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $tx->type === 'income' ? '+' : '-' }} Rp
                                {{ number_format($tx->amount, 0, ',', '.') }}
                            </div>
                            <div class="text-[10px] uppercase font-black text-slate-400 tracking-tighter">
                                {{ $tx->payment_date->diffForHumans() }}</div>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-slate-500 italic">Belum ada transaksi diinput.</div>
                @endforelse
            </div>
        </x-ui.card>

        @if ($isTreasurer)
            {{-- Budget Plans for Treasurer --}}
            <x-ui.card :title="__('Rencana Anggaran (RAB) Aktif')" separator shadow>
                <x-slot:actions>
                    <x-ui.button :label="__('Lihat Semua')" :link="route('financial.budget-plans')" wire:navigate ghost />
                </x-slot:actions>

                <div class="divide-y divide-slate-100 dark:divide-slate-800/50">
                    @forelse($activeBudgetPlans as $plan)
                        <div
                            class="py-4 flex justify-between items-center transition-all hover:bg-slate-50/50 dark:hover:bg-slate-800/30 -mx-4 px-4 rounded-xl group">
                            <div class="flex gap-4 items-center">
                                <div
                                    class="p-2.5 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-xl shadow-sm group-hover:bg-blue-100 dark:group-hover:bg-blue-900/40 transition-colors">
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
                                <div
                                    class="text-xs font-bold px-2 py-0.5 rounded-md bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800 uppercase tracking-widest">
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
            {{-- Recent Attendance for Admin/Guru --}}
            <x-ui.card :title="__('Input Presensi Terakhir')" separator shadow>
                <x-slot:actions>
                    <x-ui.button :label="__('Lihat Semua')" :link="route('academic.attendance')" wire:navigate ghost />
                </x-slot:actions>

                <div class="divide-y divide-slate-100 dark:divide-slate-800/50">
                    @forelse($recentAttendance as $att)
                        <div
                            class="py-4 flex justify-between items-center transition-all hover:bg-slate-50/50 dark:hover:bg-slate-800/30 -mx-4 px-4 rounded-xl group">
                            <div class="flex gap-4 items-center">
                                <div
                                    class="p-2.5 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-xl shadow-sm group-hover:bg-blue-100 dark:group-hover:bg-blue-900/40 transition-colors">
                                    <x-ui.icon name="o-clipboard-document-check" class="size-6" />
                                </div>
                                <div>
                                    <div class="font-bold text-slate-900 dark:text-white">Kelas
                                        {{ $att->classroom->name }}</div>
                                    <div class="text-xs font-medium text-slate-500 dark:text-slate-400">
                                        {{ $att->subject?->name ?? 'Harian' }} • {{ $att->date->format('d M Y') }}
                                    </div>
                                </div>
                            </div>
                            <div
                                class="text-xs font-bold px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
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
