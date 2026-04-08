<?php

declare(strict_types=1);

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\StudentBilling;
use App\Models\Transaction;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public string $tab = 'financial';

    public array $selected_billings = [];

    public bool $selectAll = false;

    public bool $broadcastModal = false;

    public ?string $wa_message = null;

    // Financial Filters
    public ?int $level_id = null;

    public ?string $start_date = null;

    public ?string $end_date = null;

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

            $financialData = (clone $query)->latest()->get();

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
        }

        $attendanceData = [];
        if ($this->tab === 'attendance') {
            $attendanceData = Attendance::with(['classroom', 'subject'])
                ->when($this->classroom_id, fn ($q) => $q->where('classroom_id', $this->classroom_id))
                ->when($this->academic_year_id, fn ($q) => $q->where('academic_year_id', $this->academic_year_id))
                ->latest()
                ->get();
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
                ->get();
        }

        return [
            'financialData' => $financialData,
            'attendanceData' => $attendanceData,
            'arrearsData' => $arrearsData,
            'summary' => $summary,
            'levelSummary' => $levelSummary,
            'levels' => auth()->user()->isAdmin() || auth()->user()->isYayasan()
                ? \App\Models\Level::all()
                : (auth()->user()->managed_level_id ? \App\Models\Level::where('id', auth()->user()->managed_level_id)->get() : collect()),
            'classrooms' => Classroom::when(! auth()->user()->isAdmin() && ! auth()->user()->isYayasan() && auth()->user()->managed_level_id, function ($q) {
                $q->where('level_id', auth()->user()->managed_level_id);
            })->get(),
            'years' => AcademicYear::all(),
        ];
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
        @include('livewire.admin.reports.table-attendance')
        @include('livewire.admin.reports.table-arrears')
    </div>

    @include('livewire.admin.reports.modal-broadcast')
</div>
