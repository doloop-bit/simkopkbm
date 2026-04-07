<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Transaction;
use App\Models\StudentBilling;
use App\Models\FeeCategory;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\AcademicYear;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.admin.layouts.app')] class extends Component {
    public string $tab = 'financial';
    
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
                    $q->whereHas('feeCategory', fn ($fcq) => $fcq->where('level_id', $this->level_id));
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

        return [
            'financialData' => $financialData,
            'attendanceData' => $attendanceData,
            'summary' => $summary,
            'levelSummary' => $levelSummary,
            'levels' => \App\Models\Level::all(),
            'classrooms' => Classroom::all(),
            'years' => AcademicYear::all(),
        ];
    }
}; ?>

<div class="p-6 space-y-8 text-slate-900 dark:text-white pb-24 md:pb-6">
    <x-ui.header :title="__('Analitik & Pelaporan')" :subtitle="__('Pantau indikator performa utama keuangan dan tingkat partisipasi akademik secara komprehensif.')" separator />

    {{-- Report Navigation Tabs --}}
    <div class="flex items-center gap-1 p-1 bg-slate-100 dark:bg-slate-900 rounded-2xl w-fit shadow-inner">
        <x-ui.button 
            wire:click="$set('tab', 'financial')" 
            :label="__('Keuangan')" 
            icon="o-banknotes" 
            class="rounded-xl px-6 font-semibold py-2 h-auto {{ $tab === 'financial' ? 'bg-white text-primary shadow-sm border-none' : 'btn-ghost text-slate-500' }}" 
        />
        <x-ui.button 
            wire:click="$set('tab', 'attendance')" 
            :label="__('Presensi')" 
            icon="o-clipboard-document-check" 
            class="rounded-xl px-6 font-semibold py-2 h-auto {{ $tab === 'attendance' ? 'bg-white text-primary shadow-sm border-none' : 'btn-ghost text-slate-500' }}" 
        />
    </div>

    {{-- Dynamic Content & Filters --}}
    <div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-700">
        {{-- Yayasan Levelized Summary Table --}}
        @if($tab === 'financial' && count($levelSummary) > 0)
            <x-ui.card shadow padding="false" class="border-none overflow-hidden ring-1 ring-slate-100 dark:ring-slate-800">
                <div class="p-6 border-b border-slate-50 dark:border-slate-800 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-3">
                        <div class="size-10 rounded-2xl bg-primary/10 flex items-center justify-center text-primary">
                            <x-ui.icon name="o-presentation-chart-line" class="size-5" />
                        </div>
                        <div>
                            <h3 class="font-black text-slate-800 dark:text-white">{{ __('Ikhtisar Keuangan per Jenjang') }}</h3>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ __('Performa dan ketersediaan dana berdasarkan unit pendidikan') }}</p>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-slate-900/50">
                                <th class="px-6 py-4 text-[10px] font-black uppercase text-slate-400 tracking-widest">{{ __('Jenjang/Unit') }}</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase text-slate-400 tracking-widest text-right">{{ __('Total Masuk') }}</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase text-slate-400 tracking-widest text-right">{{ __('Total Keluar') }}</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase text-slate-400 tracking-widest text-right">{{ __('Saldo (Net)') }}</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase text-slate-400 tracking-widest text-center">{{ __('Efisiensi') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($levelSummary as $row)
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <span class="font-black text-slate-700 dark:text-slate-200 tracking-tight">{{ $row['name'] }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="font-bold text-emerald-600 font-mono">Rp {{ number_format($row['income'], 0, ',', '.') }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="font-bold text-rose-600 font-mono">Rp {{ number_format($row['expense'], 0, ',', '.') }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex flex-col">
                                            <span class="font-black {{ $row['balance'] >= 0 ? 'text-primary' : 'text-rose-700' }} font-mono">
                                                Rp {{ number_format($row['balance'], 0, ',', '.') }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col items-center gap-2">
                                            @php 
                                                $eff = $row['income'] > 0 ? round(($row['expense'] / $row['income']) * 100) : 0;
                                                $color = $eff > 90 ? 'bg-rose-500' : ($eff > 70 ? 'bg-amber-500' : 'bg-emerald-500');
                                            @endphp
                                            <span class="text-[10px] font-black text-slate-500">{{ $eff }}%</span>
                                            <div class="w-16 h-1 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                                <div class="h-full {{ $color }}" style="width: {{ min(100, $eff) }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-900 text-white font-black">
                                <td class="px-6 py-4 uppercase tracking-widest text-[10px]">{{ __('Total Gabungan') }}</td>
                                <td class="px-6 py-4 text-right font-mono text-sm">Rp {{ number_format($summary['income'], 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right font-mono text-sm">Rp {{ number_format($summary['expense'], 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right font-mono text-sm">Rp {{ number_format($summary['income'] - $summary['expense'], 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-center">
                                    @php 
                                        $totalEff = $summary['income'] > 0 ? round(($summary['expense'] / $summary['income']) * 100) : 0;
                                    @endphp
                                    <span class="text-xs">{{ $totalEff }}%</span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </x-ui.card>
        @endif
        {{-- Yayasan Financial Summary --}}
        @if($tab === 'financial')
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="p-6 bg-emerald-50 dark:bg-emerald-950/20 rounded-3xl border border-emerald-100 dark:border-emerald-900/50 space-y-3 relative overflow-hidden group">
                    <div class="text-[10px] font-black uppercase text-emerald-600/60 tracking-widest">{{ __('Total Pemasukan') }}</div>
                    <div class="text-3xl font-black text-emerald-700 dark:text-emerald-400 font-mono tracking-tighter">
                        Rp {{ number_format($summary['income'], 0, ',', '.') }}
                    </div>
                    <x-ui.icon name="o-arrow-trending-up" class="absolute -right-4 -bottom-4 size-24 text-emerald-500/10 group-hover:scale-110 transition-transform" />
                </div>

                <div class="p-6 bg-rose-50 dark:bg-rose-950/20 rounded-3xl border border-rose-100 dark:border-rose-900/50 space-y-3 relative overflow-hidden group">
                    <div class="text-[10px] font-black uppercase text-rose-600/60 tracking-widest">{{ __('Total Pengeluaran') }}</div>
                    <div class="text-3xl font-black text-rose-700 dark:text-rose-400 font-mono tracking-tighter">
                        Rp {{ number_format($summary['expense'], 0, ',', '.') }}
                    </div>
                    <x-ui.icon name="o-arrow-trending-down" class="absolute -right-4 -bottom-4 size-24 text-rose-500/10 group-hover:scale-110 transition-transform" />
                </div>

                <div class="p-6 bg-amber-50 dark:bg-amber-950/20 rounded-3xl border border-amber-100 dark:border-amber-900/50 space-y-3 relative overflow-hidden group">
                    <div class="text-[10px] font-black uppercase text-amber-600/60 tracking-widest">{{ __('Total Piutang/Tunggakan') }}</div>
                    <div class="text-3xl font-black text-amber-700 dark:text-amber-400 font-mono tracking-tighter">
                        Rp {{ number_format($summary['tunggakan'], 0, ',', '.') }}
                    </div>
                    <x-ui.icon name="o-exclamation-circle" class="absolute -right-4 -bottom-4 size-24 text-amber-500/10 group-hover:scale-110 transition-transform" />
                </div>
            </div>
        @endif
        {{-- Specialized Filters Card --}}
        <x-ui.card shadow padding="false" class="border-none ring-1 ring-slate-100 dark:ring-slate-800 bg-slate-50/30 dark:bg-slate-900/10">
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-end">
                    @if($tab === 'financial')
                        <div class="md:col-span-4">
                            <x-ui.select 
                                wire:model.live="level_id" 
                                :label="__('Jenjang')" 
                                :placeholder="__('Seluruh Jenjang')"
                                :options="$levels"
                            />
                        </div>
                        <div class="md:col-span-3">
                            <x-ui.input wire:model.live="start_date" type="date" :label="__('Rentang Awal')" />
                        </div>
                        <div class="md:col-span-3">
                            <x-ui.input wire:model.live="end_date" type="date" :label="__('Rentang Akhir')" />
                        </div>
                    @endif

                    @if($tab === 'attendance')
                        <div class="md:col-span-5">
                            <x-ui.select 
                                wire:model.live="academic_year_id" 
                                :label="__('Tahun Ajaran')" 
                                :options="$years"
                            />
                        </div>
                        <div class="md:col-span-5">
                            <x-ui.select 
                                wire:model.live="classroom_id" 
                                :label="__('Kelas / Rombel')" 
                                :placeholder="__('Seluruh Kelas')"
                                :options="$classrooms"
                            />
                        </div>
                    @endif
                    
                    <div class="md:col-span-2">
                        <x-ui.button 
                            wire:click="downloadFinancialReport" 
                            wire:loading.attr="disabled"
                            :label="__('Ekspor CSV')" 
                            icon="o-arrow-down-tray" 
                            class="btn-primary w-full shadow-lg shadow-primary/20" 
                            spinner="downloadFinancialReport"
                        />
                    </div>
                </div>
            </div>
        </x-ui.card>

        {{-- Analytical Results --}}
        <x-ui.card shadow padding="false" class="border-none ring-1 ring-slate-100 dark:ring-slate-800 overflow-hidden">
            @if($tab === 'financial')
                <x-ui.table :headers="[
                    ['key' => 'payment_date', 'label' => __('Tanggal')],
                    ['key' => 'description', 'label' => __('Nama Siswa/Detail')],
                    ['key' => 'category', 'label' => __('Kategori/RAB')],
                    ['key' => 'payment_method', 'label' => __('Metode'), 'class' => 'text-[10px] uppercase font-bold tracking-wider'],
                    ['key' => 'amount_real', 'label' => __('Realisasi'), 'class' => 'text-right font-bold']
                ]" :rows="$financialData">
                    @scope('cell_payment_date', $tx)
                        <span class="text-xs font-medium text-slate-500 font-mono italic">{{ $tx->payment_date->format('d/m/Y') }}</span>
                    @endscope

                    @scope('cell_category', $tx)
                        <div class="flex flex-col">
                            <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ $tx->billing?->feeCategory?->name ?? $tx->budgetPlan?->title ?? __('Umum') }}</span>
                            @if($tx->budgetItem)
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">{{ $tx->budgetItem->name }}</span>
                            @endif
                        </div>
                    @endscope

                    @scope('cell_description', $tx)
                        <div class="flex flex-col">
                            <span class="font-bold text-slate-900 dark:text-white">{{ $tx->billing?->student?->name ?? $tx->budgetItem?->name ?? '-' }}</span>
                            @if($tx->reference_number)
                                <span class="text-[9px] font-mono text-slate-400">{{ $tx->reference_number }}</span>
                            @endif
                        </div>
                    @endscope

                    @scope('cell_amount_real', $tx)
                        <div class="text-right flex flex-col">
                            <div class="flex items-baseline justify-end gap-1">
                                <span class="text-xs font-black {{ $tx->type === 'income' ? 'text-emerald-600' : 'text-rose-600' }} font-mono">
                                    {{ $tx->type === 'income' ? '+' : '-' }}Rp {{ number_format($tx->amount + ($tx->adjustment_amount ?? 0), 0, ',', '.') }}
                                </span>
                            </div>
                            @if($tx->adjustment_amount)
                                <span class="text-[9px] font-bold text-slate-400 lowercase tracking-tighter italic">
                                    (adj: {{ $tx->adjustment_amount > 0 ? '+' : '' }}{{ number_format($tx->adjustment_amount, 0, ',', '.') }})
                                </span>
                            @endif
                        </div>
                    @endscope

                    <x-slot:append>
                        @php $totalIncome = $financialData->sum('amount'); @endphp
                        <tr class="bg-slate-50 dark:bg-slate-800/50">
                            <td colspan="4" class="p-4 text-right">
                                <span class="font-bold text-slate-500 uppercase tracking-wider text-xs">{{ __('Total Akumulasi') }}</span>
                            </td>
                            <td class="p-4 text-right">
                                <span class="text-lg font-bold text-primary font-mono whitespace-nowrap">
                                    Rp {{ number_format($totalIncome, 0, ',', '.') }}
                                </span>
                            </td>
                        </tr>
                    </x-slot:append>
                </x-ui.table>
            @endif

            @if($tab === 'attendance')
                <x-ui.table :headers="[
                    ['key' => 'date', 'label' => __('Tanggal')],
                    ['key' => 'classroom.name', 'label' => __('Kelas')],
                    ['key' => 'subject_name', 'label' => __('Materi')],
                    ['key' => 'percentage', 'label' => __('Kehadiran'), 'class' => 'text-center']
                ]" :rows="$attendanceData">
                    @scope('cell_date', $att)
                        <span class="text-xs font-medium text-slate-500 font-mono italic">{{ $att->date->format('d/m/Y') }}</span>
                    @endscope

                    @scope('cell_classroom_name', $att)
                         <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $att->classroom?->name }}</span>
                    @endscope

                    @scope('cell_subject_name', $att)
                        <span class="text-xs text-slate-500">{{ $att->subject?->name ?? __('Presensi Harian') }}</span>
                    @endscope

                    @scope('cell_percentage', $att)
                        @php 
                            $items = $att->items;
                            $present = $items->filter(fn($i) => $i->status === 'h')->count();
                            $total = $items->count();
                            $percent = $total > 0 ? round(($present / $total) * 100) : 0;
                            $barColor = $percent >= 80 ? 'bg-emerald-500' : ($percent >= 60 ? 'bg-amber-500' : 'bg-rose-500');
                        @endphp
                        <div class="flex flex-col items-center gap-1.5">
                            <div class="flex items-baseline gap-1">
                                <span class="text-base font-bold text-slate-900 dark:text-white">{{ $percent }}%</span>
                                <span class="text-[10px] font-medium text-slate-400">({{ $present }}/{{ $total }})</span>
                            </div>
                            <div class="w-24 h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden shadow-inner flex">
                                <div class="h-full {{ $barColor }} transition-all duration-1000 ease-out" style="width: {{ $percent }}%"></div>
                            </div>
                        </div>
                    @endscope
                </x-ui.table>
            @endif
        </x-ui.card>
    </div>
</div>
