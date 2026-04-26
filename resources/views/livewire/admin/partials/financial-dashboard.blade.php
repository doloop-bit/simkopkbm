{{-- Financial Dashboard Charts Section --}}
{{-- Loaded via Chart.js CDN - only rendered when user has financial access --}}

@php
    $hasFinancialData = !empty($chartData);
@endphp

@if($hasFinancialData)
<div class="space-y-6" id="financial-dashboard-section">
    {{-- Section Header --}}
    <div class="flex items-center gap-3 pt-2">
        <div class="p-2 bg-emerald-50 dark:bg-emerald-950/30 rounded-xl">
            <x-ui.icon name="o-chart-bar-square" class="size-6 text-emerald-600 dark:text-emerald-400" />
        </div>
        <div>
            <h2 class="text-lg font-extrabold text-slate-900 dark:text-white tracking-tight">{{ __('Analisis Keuangan') }}</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">{{ __('Ringkasan keuangan dan anggaran') }}</p>
        </div>
    </div>

    {{-- Row 1: Cash Flow + Income Composition --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- ① Cash Flow Chart (2 cols wide) --}}
        <x-ui.card class="lg:col-span-2" padding="false">
            <div class="p-5 pb-3">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Arus Kas Bulanan') }}</h3>
                <p class="text-xs text-slate-400 mt-0.5">{{ __('6 bulan terakhir') }}</p>
            </div>
            <div class="px-5 pb-5">
                <div class="relative h-64"
                     x-data="cashFlowChart(@js($chartData['cashFlow']))"
                     x-init="initChart()"
                >
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>
        </x-ui.card>

        {{-- ② Income Composition Donut --}}
        <x-ui.card padding="false">
            <div class="p-5 pb-3">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Komposisi Pemasukan') }}</h3>
                <p class="text-xs text-slate-400 mt-0.5">{{ __('Bulan ini') }}</p>
            </div>
            <div class="px-5 pb-5">
                @if(!empty($chartData['incomeComposition']['labels']))
                    <div class="relative h-56"
                         x-data="compositionChart(@js($chartData['incomeComposition']), 'income')"
                         x-init="initChart()"
                    >
                        <canvas x-ref="canvas"></canvas>
                    </div>
                    <div class="mt-4 space-y-2">
                        @foreach($chartData['incomeComposition']['labels'] as $i => $label)
                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="size-2.5 rounded-full shrink-0" style="background-color: {{ $chartData['incomeComposition']['colors'][$i] }}"></span>
                                    <span class="text-slate-600 dark:text-slate-400 font-medium truncate max-w-[120px]">{{ $label }}</span>
                                </div>
                                <span class="font-bold text-slate-900 dark:text-white font-mono">Rp {{ number_format($chartData['incomeComposition']['values'][$i], 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="h-56 flex items-center justify-center text-sm text-slate-400 italic">
                        {{ __('Belum ada pemasukan bulan ini') }}
                    </div>
                @endif
            </div>
        </x-ui.card>
    </div>

    {{-- Row 2: Expense Composition + Billing Collection Rate --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- ③ Expense Composition Donut --}}
        <x-ui.card padding="false">
            <div class="p-5 pb-3">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Komposisi Pengeluaran') }}</h3>
                <p class="text-xs text-slate-400 mt-0.5">{{ __('Bulan ini') }}</p>
            </div>
            <div class="px-5 pb-5">
                @if(!empty($chartData['expenseComposition']['labels']))
                    <div class="relative h-56"
                         x-data="compositionChart(@js($chartData['expenseComposition']), 'expense')"
                         x-init="initChart()"
                    >
                        <canvas x-ref="canvas"></canvas>
                    </div>
                    <div class="mt-4 space-y-2">
                        @foreach($chartData['expenseComposition']['labels'] as $i => $label)
                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="size-2.5 rounded-full shrink-0" style="background-color: {{ $chartData['expenseComposition']['colors'][$i] }}"></span>
                                    <span class="text-slate-600 dark:text-slate-400 font-medium truncate max-w-[120px]">{{ $label }}</span>
                                </div>
                                <span class="font-bold text-slate-900 dark:text-white font-mono">Rp {{ number_format($chartData['expenseComposition']['values'][$i], 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="h-56 flex items-center justify-center text-sm text-slate-400 italic">
                        {{ __('Belum ada pengeluaran bulan ini') }}
                    </div>
                @endif
            </div>
        </x-ui.card>

        {{-- ④ Billing Collection Rate (Pure Tailwind) --}}
        <x-ui.card class="lg:col-span-2" padding="false">
            <div class="p-5 pb-3">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Tingkat Koleksi Tagihan') }}</h3>
                <p class="text-xs text-slate-400 mt-0.5">{{ __('Tahun ajaran aktif') }}</p>
            </div>
            <div class="px-5 pb-5">
                @if(!empty($chartData['collectionRate']))
                    <div class="space-y-4">
                        @foreach($chartData['collectionRate'] as $level)
                            @php
                                $percentage = $level['total'] > 0 ? round(($level['paid'] / $level['total']) * 100) : 0;
                                $barColor = match(true) {
                                    $percentage >= 80 => 'bg-emerald-500',
                                    $percentage >= 50 => 'bg-amber-500',
                                    default => 'bg-rose-500',
                                };
                                $textColor = match(true) {
                                    $percentage >= 80 => 'text-emerald-600 dark:text-emerald-400',
                                    $percentage >= 50 => 'text-amber-600 dark:text-amber-400',
                                    default => 'text-rose-600 dark:text-rose-400',
                                };
                            @endphp
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $level['name'] }}</span>
                                        <span class="text-[10px] font-bold {{ $textColor }} px-1.5 py-0.5 rounded-md bg-slate-50 dark:bg-slate-800">{{ $percentage }}%</span>
                                    </div>
                                    <span class="text-xs text-slate-500 font-mono">
                                        Rp {{ number_format($level['paid'], 0, ',', '.') }} / {{ number_format($level['total'], 0, ',', '.') }}
                                    </span>
                                </div>
                                <div class="h-3 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                    <div class="{{ $barColor }} h-full rounded-full transition-all duration-700 ease-out" style="width: {{ $percentage }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-8 text-center text-sm text-slate-400 italic">
                        {{ __('Belum ada data tagihan.') }}
                    </div>
                @endif
            </div>
        </x-ui.card>
    </div>

    {{-- Row 3: Budget Realization + RAB Yearly Trend --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- ⑤ Budget Realization (Horizontal Bar) --}}
        <x-ui.card padding="false">
            <div class="p-5 pb-3">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Realisasi RAB') }}</h3>
                <p class="text-xs text-slate-400 mt-0.5">{{ __('Anggaran aktif vs realisasi') }}</p>
            </div>
            <div class="px-5 pb-5">
                @if(!empty($chartData['budgetRealization']['labels']))
                    <div class="relative h-64"
                         x-data="budgetRealizationChart(@js($chartData['budgetRealization']))"
                         x-init="initChart()"
                    >
                        <canvas x-ref="canvas"></canvas>
                    </div>
                @else
                    <div class="h-64 flex items-center justify-center text-sm text-slate-400 italic">
                        {{ __('Belum ada RAB aktif.') }}
                    </div>
                @endif
            </div>
        </x-ui.card>

        {{-- ⑥ RAB Yearly Trend (Line) --}}
        <x-ui.card padding="false">
            <div class="p-5 pb-3">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Tren RAB Tahunan') }}</h3>
                <p class="text-xs text-slate-400 mt-0.5">{{ __('Akumulasi pengeluaran vs pagu anggaran') }} {{ now()->year }}</p>
            </div>
            <div class="px-5 pb-5">
                @if(!empty($chartData['rabTrend']['months']))
                    <div class="relative h-64"
                         x-data="rabTrendChart(@js($chartData['rabTrend']))"
                         x-init="initChart()"
                    >
                        <canvas x-ref="canvas"></canvas>
                    </div>
                @else
                    <div class="h-64 flex items-center justify-center text-sm text-slate-400 italic">
                        {{ __('Belum ada data pengeluaran RAB tahun ini.') }}
                    </div>
                @endif
            </div>
        </x-ui.card>
    </div>

    {{-- Row 4: Outstanding Debts Table (Admin + Bendahara only) --}}
    @if($showDebtors && !empty($chartData['topDebtors']))
        <x-ui.card padding="false">
            <div class="p-5 pb-3 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Siswa Menunggak') }}</h3>
                    <p class="text-xs text-slate-400 mt-0.5">{{ __('10 siswa dengan tunggakan terbesar') }}</p>
                </div>
                <x-ui.button :label="__('Lihat Semua')" :link="route('financial.billings')" wire:navigate ghost class="text-xs" />
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-y border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                            <th class="text-left px-5 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">#</th>
                            <th class="text-left px-5 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('Siswa') }}</th>
                            <th class="text-left px-5 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('Jenjang') }}</th>
                            <th class="text-right px-5 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('Tunggakan') }}</th>
                            <th class="text-center px-5 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('Tagihan') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($chartData['topDebtors'] as $i => $debtor)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="px-5 py-3">
                                    <span class="text-xs font-black text-slate-300 dark:text-slate-600">{{ $i + 1 }}</span>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="font-bold text-slate-900 dark:text-white">{{ $debtor['name'] }}</span>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 uppercase tracking-wider">{{ $debtor['level'] }}</span>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <span class="font-bold font-mono text-rose-600 dark:text-rose-400">Rp {{ number_format($debtor['unpaid'], 0, ',', '.') }}</span>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <span class="text-xs font-bold text-slate-500">{{ $debtor['billing_count'] }} {{ __('tagihan') }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    @endif
</div>

{{-- Chart.js CDN + Alpine Components --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
    const isDark = () => document.documentElement.classList.contains('dark');
    const gridColor = () => isDark() ? 'rgba(148, 163, 184, 0.1)' : 'rgba(148, 163, 184, 0.2)';
    const textColor = () => isDark() ? 'rgb(148, 163, 184)' : 'rgb(100, 116, 139)';

    const formatRupiah = (value) => 'Rp ' + new Intl.NumberFormat('id-ID').format(value);

    const baseTooltip = {
        backgroundColor: isDark() ? 'rgb(30, 41, 59)' : 'rgb(255, 255, 255)',
        titleColor: isDark() ? 'rgb(226, 232, 240)' : 'rgb(15, 23, 42)',
        bodyColor: isDark() ? 'rgb(148, 163, 184)' : 'rgb(100, 116, 139)',
        borderColor: isDark() ? 'rgb(51, 65, 85)' : 'rgb(226, 232, 240)',
        borderWidth: 1,
        padding: 12,
        cornerRadius: 12,
        titleFont: { weight: 'bold', size: 13 },
        bodyFont: { size: 12 },
        displayColors: true,
        boxPadding: 4,
    };

    document.addEventListener('alpine:init', () => {
        // ① Cash Flow Area Chart
        Alpine.data('cashFlowChart', (data) => ({
            chart: null,
            initChart() {
                this.chart = new Chart(this.$refs.canvas, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                label: 'Pemasukan',
                                data: data.income,
                                borderColor: 'rgb(16, 185, 129)',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                fill: true,
                                tension: 0.4,
                                borderWidth: 2.5,
                                pointRadius: 4,
                                pointBackgroundColor: 'rgb(16, 185, 129)',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointHoverRadius: 6,
                            },
                            {
                                label: 'Pengeluaran',
                                data: data.expense,
                                borderColor: 'rgb(244, 63, 94)',
                                backgroundColor: 'rgba(244, 63, 94, 0.08)',
                                fill: true,
                                tension: 0.4,
                                borderWidth: 2.5,
                                pointRadius: 4,
                                pointBackgroundColor: 'rgb(244, 63, 94)',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointHoverRadius: 6,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                align: 'end',
                                labels: { color: textColor(), font: { size: 11, weight: 'bold' }, usePointStyle: true, pointStyle: 'circle', padding: 16 }
                            },
                            tooltip: {
                                ...baseTooltip,
                                callbacks: { label: (ctx) => ctx.dataset.label + ': ' + formatRupiah(ctx.parsed.y) }
                            }
                        },
                        scales: {
                            x: { grid: { display: false }, ticks: { color: textColor(), font: { size: 11, weight: '600' } } },
                            y: {
                                grid: { color: gridColor() },
                                ticks: { color: textColor(), font: { size: 11 }, callback: (v) => v >= 1000000 ? (v / 1000000).toFixed(1) + 'M' : v >= 1000 ? (v / 1000).toFixed(0) + 'k' : v },
                                beginAtZero: true,
                            }
                        }
                    }
                });
            }
        }));

        // ②③ Composition Donut Chart (reusable for income & expense)
        Alpine.data('compositionChart', (data, type) => ({
            chart: null,
            initChart() {
                const total = data.values.reduce((a, b) => a + b, 0);
                this.chart = new Chart(this.$refs.canvas, {
                    type: 'doughnut',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            data: data.values,
                            backgroundColor: data.colors,
                            borderColor: isDark() ? 'rgb(15, 23, 42)' : 'rgb(255, 255, 255)',
                            borderWidth: 3,
                            hoverBorderWidth: 0,
                            hoverOffset: 6,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '68%',
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                ...baseTooltip,
                                callbacks: {
                                    label: (ctx) => {
                                        const pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                                        return ctx.label + ': ' + formatRupiah(ctx.parsed) + ' (' + pct + '%)';
                                    }
                                }
                            }
                        }
                    },
                    plugins: [{
                        id: 'centerText',
                        afterDraw(chart) {
                            const { ctx, chartArea: { width, height, top } } = chart;
                            ctx.save();
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'middle';
                            const centerY = top + height / 2;
                            const centerX = width / 2 + chart.chartArea.left;

                            ctx.font = 'bold 10px system-ui';
                            ctx.fillStyle = isDark() ? 'rgb(148, 163, 184)' : 'rgb(100, 116, 139)';
                            ctx.fillText(type === 'income' ? 'TOTAL MASUK' : 'TOTAL KELUAR', centerX, centerY - 10);

                            const formatted = total >= 1000000 ? (total / 1000000).toFixed(1) + 'M' : total >= 1000 ? (total / 1000).toFixed(0) + 'k' : total;
                            ctx.font = 'bold 18px system-ui';
                            ctx.fillStyle = isDark() ? 'rgb(226, 232, 240)' : 'rgb(15, 23, 42)';
                            ctx.fillText('Rp ' + formatted, centerX, centerY + 12);
                            ctx.restore();
                        }
                    }]
                });
            }
        }));

        // ⑤ Budget Realization Horizontal Bar
        Alpine.data('budgetRealizationChart', (data) => ({
            chart: null,
            initChart() {
                this.chart = new Chart(this.$refs.canvas, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                label: 'Anggaran (RAB)',
                                data: data.planned,
                                backgroundColor: isDark() ? 'rgba(100, 116, 139, 0.3)' : 'rgba(148, 163, 184, 0.3)',
                                borderColor: isDark() ? 'rgb(100, 116, 139)' : 'rgb(148, 163, 184)',
                                borderWidth: 1,
                                borderRadius: 6,
                                barPercentage: 0.6,
                                categoryPercentage: 0.7,
                            },
                            {
                                label: 'Realisasi',
                                data: data.realized,
                                backgroundColor: data.realized.map((v, i) => v > data.planned[i] ? 'rgba(244, 63, 94, 0.7)' : 'rgba(16, 185, 129, 0.7)'),
                                borderColor: data.realized.map((v, i) => v > data.planned[i] ? 'rgb(244, 63, 94)' : 'rgb(16, 185, 129)'),
                                borderWidth: 1,
                                borderRadius: 6,
                                barPercentage: 0.6,
                                categoryPercentage: 0.7,
                            }
                        ]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                align: 'end',
                                labels: { color: textColor(), font: { size: 11, weight: 'bold' }, usePointStyle: true, pointStyle: 'rect', padding: 16 }
                            },
                            tooltip: {
                                ...baseTooltip,
                                callbacks: {
                                    label: (ctx) => {
                                        const pct = data.planned[ctx.dataIndex] > 0
                                            ? ((data.realized[ctx.dataIndex] / data.planned[ctx.dataIndex]) * 100).toFixed(1)
                                            : 0;
                                        return ctx.dataset.label + ': ' + formatRupiah(ctx.parsed.x) + (ctx.datasetIndex === 1 ? ' (' + pct + '%)' : '');
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { color: gridColor() },
                                ticks: { color: textColor(), font: { size: 11 }, callback: (v) => v >= 1000000 ? (v / 1000000).toFixed(1) + 'M' : v >= 1000 ? (v / 1000).toFixed(0) + 'k' : v },
                                beginAtZero: true,
                            },
                            y: { grid: { display: false }, ticks: { color: textColor(), font: { size: 11, weight: '600' } } }
                        }
                    }
                });
            }
        }));

        // ⑥ RAB Yearly Trend Line
        Alpine.data('rabTrendChart', (data) => ({
            chart: null,
            initChart() {
                this.chart = new Chart(this.$refs.canvas, {
                    type: 'line',
                    data: {
                        labels: data.months,
                        datasets: [
                            {
                                label: 'Akumulasi Pengeluaran',
                                data: data.cumulative,
                                borderColor: 'rgb(16, 185, 129)',
                                backgroundColor: 'rgba(16, 185, 129, 0.08)',
                                fill: true,
                                tension: 0.3,
                                borderWidth: 2.5,
                                pointRadius: 4,
                                pointBackgroundColor: 'rgb(16, 185, 129)',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointHoverRadius: 6,
                            },
                            {
                                label: 'Pagu Anggaran',
                                data: data.ceiling,
                                borderColor: 'rgb(244, 63, 94)',
                                borderDash: [8, 4],
                                borderWidth: 2,
                                pointRadius: 0,
                                fill: false,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                align: 'end',
                                labels: { color: textColor(), font: { size: 11, weight: 'bold' }, usePointStyle: true, padding: 16 }
                            },
                            tooltip: {
                                ...baseTooltip,
                                callbacks: { label: (ctx) => ctx.dataset.label + ': ' + formatRupiah(ctx.parsed.y) }
                            }
                        },
                        scales: {
                            x: { grid: { display: false }, ticks: { color: textColor(), font: { size: 11, weight: '600' } } },
                            y: {
                                grid: { color: gridColor() },
                                ticks: { color: textColor(), font: { size: 11 }, callback: (v) => v >= 1000000 ? (v / 1000000).toFixed(1) + 'M' : v >= 1000 ? (v / 1000).toFixed(0) + 'k' : v },
                                beginAtZero: true,
                            }
                        }
                    }
                });
            }
        }));
    });
</script>
@endif
