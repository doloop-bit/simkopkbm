{{-- Financial Dashboard Charts Section --}}
{{-- Loaded via Chart.js CDN - only rendered when user has financial access --}}

@php
    $hasFinancialData = !empty($chartData);
@endphp

@if($hasFinancialData)
<div class="space-y-6" id="financial-dashboard-section">
    {{-- Section Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pt-2">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-emerald-50 dark:bg-emerald-950/30 rounded-xl">
                <x-ui.icon name="o-chart-bar-square" class="size-6 text-emerald-600 dark:text-emerald-400" />
            </div>
            <div>
                <h2 class="text-lg font-extrabold text-slate-900 dark:text-white tracking-tight">{{ __('Analisis Keuangan') }}</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">{{ __('Ringkasan keuangan dan anggaran') }}</p>
            </div>
        </div>
    </div>

    {{-- Row 1: Analytics --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- ① Cash Flow --}}
        <x-ui.card padding="false">
            <div class="p-5 pb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Arus Kas Bulanan') }}</h3>
                    <p class="text-xs text-slate-400 mt-0.5">{{ __('6 bulan terakhir') }}</p>
                </div>
                @if(!$isTreasurer)
                    <div class="flex items-center gap-2">
                        <x-ui.select 
                            wire:model.live="levelId" 
                            :options="$levels" 
                            option-value="id" 
                            option-label="name" 
                            placeholder="{{ __('Semua Jenjang') }}"
                            sm
                            class="w-40"
                        />
                    </div>
                @endif
            </div>
            <div class="px-5 pb-5">
                <div class="relative h-64"
                        wire:key="cash-flow-chart-{{ md5(json_encode($chartData['cashFlow'])) }}"
                        wire:ignore
                        x-data="cashFlowChart(@js($chartData['cashFlow']))"
                        x-init="initChart()"
                >
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>
        </x-ui.card>
    </div>


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




    });
</script>
@endif
