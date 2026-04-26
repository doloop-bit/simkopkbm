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
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;

    public string $tab = 'financial';

    public int $perPage = 10;

    public array $selected_billings = [];

    public bool $selectAll = false;

    public bool $broadcastModal = false;

    public ?string $wa_message = null;

    // Financial Filters
    public ?int $level_id = null;

    public ?string $start_date = null;

    public ?string $end_date = null;

    public array $rabTrendLevelIds = [];

    // Academic Filters
    public ?int $classroom_id = null;

    public ?int $academic_year_id = null;

    public function mount(): void
    {
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->format('Y-m-d');

        $activeYear = AcademicYear::where('is_active', true)->first();
        if ($activeYear) {
            $this->academic_year_id = $activeYear->id;
        }

        $this->wa_message = "Assalamu'alaikum, Bapak/Ibu Wali Murid dari {student_name}.\n\nKami menginformasikan bahwa terdapat tagihan {fee_name} periode {month} sebesar {amount} yang belum terlunasi.\n\nHarap segera melakukan konfirmasi atau pembayaran. Terima kasih.";

        if (auth()->user()->isTreasurer() && auth()->user()->managed_level_id) {
            $this->level_id = auth()->user()->managed_level_id;
        }
    }

    public function updatedLevelId($value): void
    {
        if (auth()->user()->isTreasurer() && auth()->user()->managed_level_id) {
            if (! $value || (int) $value !== auth()->user()->managed_level_id) {
                $this->level_id = auth()->user()->managed_level_id;
            }
        }
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selected_billings = StudentBilling::with(['student.studentProfile.classroom', 'feeCategory'])
                ->where('status', '!=', 'paid')
                ->when($this->level_id, function ($q) {
                    $q->where(function ($sq) {
                        $sq->whereHas('feeCategory', fn ($fcq) => $fcq->where('level_id', $this->level_id))
                            ->orWhereHas('student.studentProfile.classroom', fn ($cq) => $cq->where('level_id', $this->level_id));
                    });
                })
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->toArray();
        } else {
            $this->selected_billings = [];
        }
    }

    public function broadcast(): void
    {
        if (empty($this->selected_billings)) {
            return;
        }

        \App\Jobs\BroadcastArrearsWhatsApp::dispatch(
            array_map('intval', $this->selected_billings),
            $this->wa_message
        );

        $this->dispatch('toast', message: __(':count Antrean pesan WhatsApp sedang diproses.', ['count' => count($this->selected_billings)]), type: 'success');
        $this->broadcastModal = false;
        $this->selected_billings = [];
        $this->selectAll = false;
    }

    public function downloadFinancialReport(): mixed
    {
        $data = Transaction::with(['billing.student', 'billing.feeCategory', 'budgetPlan', 'budgetItem'])
            ->when($this->level_id, function ($q) {
                $q->where(function ($sq) {
                    $sq->whereHas('billing.feeCategory', fn ($fcq) => $fcq->where('level_id', $this->level_id))
                        ->orWhereHas('user.studentProfile.classroom', fn ($cq) => $cq->where('level_id', $this->level_id))
                        ->orWhereHas('budgetPlan', fn ($bpq) => $bpq->where('level_id', $this->level_id));
                });
            })
            ->when($this->start_date, fn ($q) => $q->whereDate('payment_date', '>=', $this->start_date))
            ->when($this->end_date, fn ($q) => $q->whereDate('payment_date', '<=', $this->end_date))
            ->latest()
            ->get();

        $filename = 'Laporan_Keuangan_'.now()->format('Ymd_His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Tanggal', 'Jenis', 'Nama Siswa/Detail', 'Kategori/RAB', 'Metode', 'Referensi', 'Nominal', 'Adjustment', 'Total Realisasi']);

            foreach ($data as $row) {
                fputcsv($file, [
                    $row->payment_date->format('d/m/Y'),
                    ucfirst($row->type),
                    $row->billing?->student?->name ?? $row->budgetItem?->name ?? 'N/A',
                    $row->billing?->feeCategory?->name ?? $row->budgetPlan?->title ?? 'N/A',
                    strtoupper($row->payment_method),
                    $row->reference_number ?? '',
                    $row->amount,
                    $row->adjustment_amount ?? 0,
                    $row->amount + ($row->adjustment_amount ?? 0),
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function with(): array
    {
        $financialData = [];
        $levelSummary = [];
        $summary = [
            'income' => 0,
            'expense' => 0,
            'tunggakan' => 0,
        ];

        $chartData = [];
        $showDebtors = false;

        if ($this->tab === 'financial') {
            $query = Transaction::with(['billing.student', 'billing.feeCategory', 'budgetPlan', 'budgetItem'])
                ->when($this->level_id, function ($q) {
                    $q->where(function ($sq) {
                        $sq->whereHas('billing.feeCategory', fn ($fcq) => $fcq->where('level_id', $this->level_id))
                            ->orWhereHas('billing.student.studentProfile.classroom', fn ($cq) => $cq->where('level_id', $this->level_id))
                            ->orWhereHas('user.studentProfile.classroom', fn ($cq) => $cq->where('level_id', $this->level_id))
                            ->orWhereHas('budgetPlan', fn ($bpq) => $bpq->where('level_id', $this->level_id));
                    });
                })
                ->when($this->start_date, fn ($q) => $q->whereDate('payment_date', '>=', $this->start_date))
                ->when($this->end_date, fn ($q) => $q->whereDate('payment_date', '<=', $this->end_date));

            $financialData = (clone $query)->latest()->paginate($this->perPage);

            $summary['income'] = (clone $query)->where('type', 'income')->sum(\DB::raw('amount + COALESCE(adjustment_amount, 0)'));
            $summary['expense'] = (clone $query)->where('type', 'expense')->sum(\DB::raw('amount + COALESCE(adjustment_amount, 0)'));
            $summary['tunggakan'] = StudentBilling::where('status', '!=', 'paid')
                ->when($this->level_id, function ($q) {
                    $q->where(function ($sq) {
                        $sq->whereHas('feeCategory', fn ($fcq) => $fcq->where('level_id', $this->level_id))
                            ->orWhereHas('student.studentProfile.classroom', fn ($cq) => $cq->where('level_id', $this->level_id));
                    });
                })
                ->sum(\DB::raw('amount - paid_amount'));

            // Level Breakdown (Always show all levels unless one is specific filtered)
            $levels = \App\Models\Level::when($this->level_id, fn ($q) => $q->where('id', $this->level_id))->get();
            $trackedIncome = 0;
            $trackedExpense = 0;

            foreach ($levels as $lvl) {
                $lvlIncome = (clone $query)
                    ->where('type', 'income')
                    ->where(function ($q) use ($lvl) {
                        $q->whereHas('billing.feeCategory', fn ($fcq) => $fcq->where('level_id', $lvl->id))
                            ->orWhereHas('billing.student.studentProfile.classroom', fn ($cq) => $cq->where('level_id', $lvl->id))
                            ->orWhereHas('user.studentProfile.classroom', fn ($cq) => $cq->where('level_id', $lvl->id));
                    })
                    ->sum(\DB::raw('amount + COALESCE(adjustment_amount, 0)'));

                $lvlExpense = (clone $query)
                    ->where('type', 'expense')
                    ->whereHas('budgetPlan', fn ($bpq) => $bpq->where('level_id', $lvl->id))
                    ->sum(\DB::raw('amount + COALESCE(adjustment_amount, 0)'));

                $levelSummary[] = [
                    'id' => $lvl->id,
                    'name' => $lvl->name,
                    'income' => (float) $lvlIncome,
                    'expense' => (float) $lvlExpense,
                    'balance' => (float) ($lvlIncome - $lvlExpense),
                ];

                $trackedIncome += $lvlIncome;
                $trackedExpense += $lvlExpense;
            }

            // Uncategorized / General Row (only show if no specific level filter or if there's orphaned data)
            if (! $this->level_id) {
                $generalIncome = $summary['income'] - $trackedIncome;
                $generalExpense = $summary['expense'] - $trackedExpense;

                if ($generalIncome > 0 || $generalExpense > 0 || $generalIncome < 0 || $generalExpense < 0) {
                    $levelSummary[] = [
                        'id' => null,
                        'name' => __('Transaksi Umum / Lainnya'),
                        'income' => (float) $generalIncome,
                        'expense' => (float) $generalExpense,
                        'balance' => (float) ($generalIncome - $generalExpense),
                    ];
                }
            }

            $user = auth()->user();
            $hasFinancialAccess = $user->isAdmin() || $user->isTreasurer() || $user->isYayasan() || $user->isHeadmaster();
            
            if ($hasFinancialAccess) {
                $chartData = $this->getFinancialChartData($user);
                $showDebtors = $user->isAdmin() || $user->isTreasurer();
            }
        }

        $attendanceData = [];
        if ($this->tab === 'attendance') {
            $attendanceData = Attendance::with(['classroom', 'subject'])
                ->when($this->classroom_id, fn ($q) => $q->where('classroom_id', $this->classroom_id))
                ->when($this->academic_year_id, fn ($q) => $q->where('academic_year_id', $this->academic_year_id))
                ->latest()
                ->paginate($this->perPage);
        }

        $arrearsData = [];
        if ($this->tab === 'arrears') {
            $arrearsData = StudentBilling::with(['student.studentProfile.classroom', 'feeCategory'])
                ->where('status', '!=', 'paid')
                ->when($this->level_id, function ($q) {
                    $q->where(function ($sq) {
                        $sq->whereHas('feeCategory', fn ($fcq) => $fcq->where('level_id', $this->level_id))
                            ->orWhereHas('student.studentProfile.classroom', fn ($cq) => $cq->where('level_id', $this->level_id));
                    });
                })
                ->latest()
                ->paginate($this->perPage);
        }

        return [
            'financialData' => $financialData,
            'attendanceData' => $attendanceData,
            'arrearsData' => $arrearsData,
            'summary' => $summary,
            'levelSummary' => $levelSummary,
            'chartData' => $chartData,
            'showDebtors' => $showDebtors,
            'levels' => auth()->user()->isAdmin() || auth()->user()->isYayasan()
                ? Level::all()
                : (auth()->user()->managed_level_id ? Level::where('id', auth()->user()->managed_level_id)->get() : collect()),
            'classrooms' => Classroom::when(! auth()->user()->isAdmin() && ! auth()->user()->isYayasan() && auth()->user()->managed_level_id, function ($q) {
                $q->where('level_id', auth()->user()->managed_level_id);
            })->get(),
            'years' => AcademicYear::all(),
            'isTreasurer' => auth()->user()->isTreasurer(),
        ];
    }

    private function getFinancialChartData(User $user): array
    {
        $treasurerLevelId = $user->isTreasurer() ? $user->managed_level_id : null;
        $filteredLevelId = $treasurerLevelId ?? $this->level_id;
        $rabTrendFilteredIds = $treasurerLevelId ? [$treasurerLevelId] : $this->rabTrendLevelIds;

        return [
            'cashFlow' => $this->getCashFlowData($filteredLevelId),
            'incomeComposition' => $this->getIncomeCompositionData($filteredLevelId),
            'expenseComposition' => $this->getExpenseCompositionData($filteredLevelId),
            'collectionRate' => $this->getCollectionRateData($treasurerLevelId),
            'budgetRealization' => $this->getBudgetRealizationData($treasurerLevelId),
            'rabTrend' => $this->getRabTrendData($rabTrendFilteredIds),
            'topDebtors' => ($user->isAdmin() || $user->isTreasurer()) ? $this->getTopDebtorsData($treasurerLevelId) : [],
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
     * ⑥ RAB yearly trend: multiple lines for active plans
     */
    private function getRabTrendData(array $levelIds = []): array
    {
        $query = BudgetPlan::where('is_active', true)->with('level');
        if (!empty($levelIds)) {
            $query->whereIn('level_id', $levelIds);
        }

        $activePlans = $query->get();
        if ($activePlans->isEmpty()) {
            return ['months' => [], 'datasets' => []];
        }

        $year = now()->year;
        $currentMonth = now()->month;
        $monthLabels = [];

        for ($m = 1; $m <= 12; $m++) {
            $monthLabels[] = Carbon::createFromDate($year, $m, 1)->translatedFormat('M');
        }

        $datasets = [];
        $palette = ['#10b981', '#3b82f6', '#f59e0b', '#8b5cf6', '#06b6d4', '#ec4899', '#14b8a6'];

        foreach ($activePlans as $index => $plan) {
            $monthlySpending = [];
            for ($m = 1; $m <= 12; $m++) {
                if ($m <= $currentMonth) {
                    $start = Carbon::createFromDate($year, $m, 1)->startOfDay();
                    $end = $start->copy()->endOfMonth()->endOfDay();

                    $monthlySpending[] = (float) Transaction::where('type', 'expense')
                        ->where('budget_plan_id', $plan->id)
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

            $color = $palette[$index % count($palette)];

            $datasets[] = [
                'label' => $plan->level ? $plan->level->name : $plan->title,
                'data' => $cumulative,
                'color' => $color,
            ];
        }

        return [
            'months' => $monthLabels,
            'datasets' => $datasets,
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

        if ($levelId) {
            $query->whereHas('student.studentProfile.classroom', function ($q) use ($levelId) {
                $q->where('level_id', $levelId);
            });
        }

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
    private function applyLevelFilter($query, ?int $levelId): void
    {
        if (!$levelId) return;

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

<div class="p-6 space-y-8 text-slate-900 dark:text-white pb-24 md:pb-6">
    @include('livewire.admin.reports.header')

    {{-- Dynamic Content & Filters --}}
    <div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-700">
        @include('livewire.admin.reports.financial-summary')
        @include('livewire.admin.reports.financial-stats')
        @include('livewire.admin.reports.filters')

        {{-- Analytical Results --}}
        @include('livewire.admin.reports.table-financial')
        @include('livewire.admin.reports.financial-graph')
        @include('livewire.admin.reports.table-attendance')
        @include('livewire.admin.reports.table-arrears')
    </div>

    @include('livewire.admin.reports.modal-broadcast')
</div>
