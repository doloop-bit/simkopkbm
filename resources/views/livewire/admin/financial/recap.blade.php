<?php

declare(strict_types=1);

use App\Models\FinancialUnit;
use App\Models\Level;
use App\Models\SchoolProfile;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public string $tab = 'bku'; // 'bku', 'bank', 'tunai'

    public int $month;
    public int $year;
    public ?int $level_id = null;
    public ?int $financial_unit_id = null;

    public float $startBalance = 0;

    public function mount(): void
    {
        $this->month = (int) now()->format('m');
        $this->year = (int) now()->format('Y');

        // Access Restriction for Treasurer
        $user = auth()->user();
        if ($user && $user->isTreasurer()) {
            $this->financial_unit_id = $user->managedFinancialUnitId();
            $this->level_id = $user->managed_level_id;
        }
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
    }

    public function exportPdf()
    {
        $data = $this->getRecapData();

        $unitName = $this->financial_unit_id ? FinancialUnit::find($this->financial_unit_id)?->name : null;
        $levelName = $unitName ?? (Level::find($this->level_id)?->name ?? __('Seluruh Unit / Jenjang'));

        $pdf = Pdf::loadView('pdf.financial-recap', [
            'transactions' => $data['transactions'],
            'startBalance' => $data['startBalance'],
            'tab' => $this->tab,
            'month' => $this->month,
            'year' => $this->year,
            'monthName' => Carbon::createFromDate($this->year, $this->month, 1)->translatedFormat('F'),
            'levelName' => $levelName,
            'schoolName' => SchoolProfile::active()?->name ?? config('app.name'),
        ]);

        $pdf->setPaper('a4', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, "Rekap-{$this->tab}-{$this->month}-{$this->year}.pdf");
    }

    public function getRecapData(): array
    {
        $user = auth()->user();
        $isTreasurer = $user?->isTreasurer();
        $managedUnitId = $isTreasurer ? $user->managedFinancialUnitId() : null;

        // Treasurer Restriction: Force unit / level
        if ($isTreasurer) {
            $this->financial_unit_id = $managedUnitId;
            $this->level_id = $user->managed_level_id;

            // If they don't have an assigned unit or level, return empty
            if (! $this->financial_unit_id && ! $this->level_id) {
                return [
                    'startBalance' => 0,
                    'transactions' => collect(),
                ];
            }
        }

        $startDate = Carbon::createFromDate($this->year, $this->month, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth()->endOfDay();

        // Calculate Starting Balance
        $baseQuery = collect();
        if ($this->tab === 'bku') {
            $baseQuery = Transaction::query();
        } elseif ($this->tab === 'bank') {
            $baseQuery = Transaction::where('payment_method', 'transfer');
        } elseif ($this->tab === 'tunai') {
            $baseQuery = Transaction::where('payment_method', 'cash');
        }

        if ($this->financial_unit_id) {
            $unitLevels = Level::where('financial_unit_id', $this->financial_unit_id)->pluck('id')->toArray();
            $baseQuery->where(function ($q) use ($unitLevels) {
                $q->where('financial_unit_id', $this->financial_unit_id);
                if (! empty($unitLevels)) {
                    $q->orWhere(function ($oq) use ($unitLevels) {
                        $oq->whereHas('billing.student.studentProfile.classroom', fn ($sq) => $sq->whereIn('level_id', $unitLevels))
                            ->orWhereHas('feeCategory', fn ($fq) => $fq->whereIn('level_id', $unitLevels))
                            ->orWhereHas('budgetPlan', fn ($bq) => $bq->whereIn('level_id', $unitLevels));
                    });
                }
            });
        } elseif ($this->level_id) {
            $baseQuery->where(function ($q) {
                $q->whereHas('billing.student.studentProfile.classroom', function ($sq) {
                    $sq->where('level_id', $this->level_id);
                })
                    ->orWhereHas('feeCategory', function ($fq) {
                        $fq->where('level_id', $this->level_id);
                    })
                    ->orWhereHas('budgetPlan', function ($bq) {
                        $bq->where('level_id', $this->level_id);
                    });
            });
        }

        // Clone for historical balance
        $historyQuery = clone $baseQuery;

        $historicalIn = $historyQuery->clone()->where('type', 'income')->where('payment_date', '<', $startDate)->sum('amount');
        $historicalOut = $historyQuery->clone()->where('type', 'expense')->where('payment_date', '<', $startDate)->sum('amount');

        $startBalance = (float) ($historicalIn - $historicalOut);
        $this->startBalance = $startBalance;

        // Current month transactions
        $transactions = $baseQuery->with(['billing.student', 'billing.feeCategory', 'feeCategory', 'budgetPlan', 'budgetItem', 'user', 'financialUnit'])
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->orderBy('payment_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return [
            'startBalance' => $startBalance,
            'transactions' => $transactions,
        ];
    }

    public function with(): array
    {
        $user = auth()->user();
        $isTreasurer = $user?->isTreasurer();
        $managedUnitId = $isTreasurer ? $user->managedFinancialUnitId() : null;

        $levels = Level::query();
        if ($isTreasurer && $user->managed_level_id) {
            $levels->where('id', $user->managed_level_id);
        }

        $financialUnits = FinancialUnit::query();
        if ($isTreasurer && $managedUnitId) {
            $financialUnits->where('id', $managedUnitId);
        }

        return array_merge($this->getRecapData(), [
            'levels' => $levels->get(),
            'financialUnits' => $financialUnits->get(),
            'isTreasurer' => $isTreasurer,
            'currentUnit' => $this->financial_unit_id ? FinancialUnit::find($this->financial_unit_id) : null,
        ]);
    }
}; ?>

<div class="p-6 space-y-6 text-slate-900 dark:text-white pb-24 md:pb-6">
    <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
        <x-ui.header :title="__('Rekapitulasi Keuangan')" :subtitle="__('Laporan BKU, Buku Bank, dan Buku Tunai bulanan.')" separator class="!mb-0" />
        
        <x-ui.button 
            wire:click="exportPdf" 
            :label="__('Download PDF')" 
            icon="o-arrow-down-tray" 
            class="btn-primary shadow-lg shadow-primary/30 min-w-[150px] shrink-0" 
            wire:loading.attr="disabled"
        />
    </div>

    {{-- Filters & Tabs --}}
    <x-ui.card shadow padding="false" class="border-none ring-1 ring-slate-100 dark:ring-slate-800 bg-white dark:bg-slate-900 overflow-hidden">
        <div class="p-6 md:flex md:items-center md:justify-between space-y-6 md:space-y-0">
            {{-- Tabs --}}
            <div class="flex items-center gap-1 p-1.5 bg-slate-100 dark:bg-slate-800 rounded-2xl w-fit">
                <button 
                    wire:click="setTab('bku')" 
                    class="px-5 py-2 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-2 {{ $tab === 'bku' ? 'bg-white dark:bg-slate-900 text-emerald-600 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200' }}"
                >
                    <x-ui.icon name="o-document-text" class="size-4" /> BKU
                </button>
                <button 
                    wire:click="setTab('bank')" 
                    class="px-5 py-2 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-2 {{ $tab === 'bank' ? 'bg-white dark:bg-slate-900 text-blue-600 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200' }}"
                >
                    <x-ui.icon name="o-building-library" class="size-4" /> Buku Bank
                </button>
                <button 
                    wire:click="setTab('tunai')" 
                    class="px-5 py-2 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-2 {{ $tab === 'tunai' ? 'bg-white dark:bg-slate-900 text-amber-600 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200' }}"
                >
                    <x-ui.icon name="o-banknotes" class="size-4" /> Buku Tunai
                </button>
            </div>

            {{-- Period & Unit / Level Filter --}}
            <div class="flex flex-wrap items-center gap-3">
                @if(!$isTreasurer)
                    <div class="w-48">
                        <x-ui.select 
                            wire:model.live="financial_unit_id" 
                            :options="$financialUnits"
                            option-value="id"
                            option-label="name"
                            :placeholder="__('Seluruh Unit Kas')"
                            icon="o-banknotes"
                        />
                    </div>
                @else
                    @if($currentUnit)
                        <div class="px-3 py-2 bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 rounded-xl text-xs font-bold flex items-center gap-2 border border-blue-100 dark:border-blue-900">
                            <x-ui.icon name="o-banknotes" class="size-4" />
                            <span>{{ $currentUnit->name }}</span>
                        </div>
                    @endif
                @endif
                <div class="w-40">
                    <x-ui.select 
                        wire:model.live="month" 
                        :options="[
                            ['id' => 1, 'name' => 'Januari'],
                            ['id' => 2, 'name' => 'Februari'],
                            ['id' => 3, 'name' => 'Maret'],
                            ['id' => 4, 'name' => 'April'],
                            ['id' => 5, 'name' => 'Mei'],
                            ['id' => 6, 'name' => 'Juni'],
                            ['id' => 7, 'name' => 'Juli'],
                            ['id' => 8, 'name' => 'Agustus'],
                            ['id' => 9, 'name' => 'September'],
                            ['id' => 10, 'name' => 'Oktober'],
                            ['id' => 11, 'name' => 'November'],
                            ['id' => 12, 'name' => 'Desember'],
                        ]"
                    />
                </div>
                <div class="w-28">
                    @php
                        $years = collect(range(now()->year - 2, now()->year + 1))->map(fn($y) => ['id' => $y, 'name' => $y]);
                    @endphp
                    <x-ui.select 
                        wire:model.live="year" 
                        :options="$years"
                    />
                </div>
            </div>
        </div>

        {{-- Analytical Summary --}}
        @php
            $currentIn = $transactions->where('type', 'income')->sum('amount');
            $currentOut = $transactions->where('type', 'expense')->sum('amount');
            $endBalance = $startBalance + $currentIn - $currentOut;
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-4 border-y border-slate-100 dark:border-slate-800 divide-y md:divide-y-0 md:divide-x divide-slate-100 dark:divide-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
            <div class="p-6">
                <div class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1">{{ __('Saldo Awal') }}</div>
                <div class="text-lg font-bold text-slate-800 dark:text-slate-200 font-mono">Rp {{ number_format($startBalance, 0, ',', '.') }}</div>
            </div>
            <div class="p-6">
                <div class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1">{{ __('Penerimaan') }}</div>
                <div class="text-lg font-bold text-emerald-600 font-mono">+ Rp {{ number_format($currentIn, 0, ',', '.') }}</div>
            </div>
            <div class="p-6">
                <div class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1">{{ __('Pengeluaran') }}</div>
                <div class="text-lg font-bold text-rose-600 font-mono">- Rp {{ number_format($currentOut, 0, ',', '.') }}</div>
            </div>
            <div class="p-6 bg-slate-100 dark:bg-slate-800">
                <div class="text-[10px] font-black uppercase text-slate-500 tracking-widest mb-1">{{ __('Saldo Akhir') }}</div>
                <div class="text-xl font-black text-primary font-mono">Rp {{ number_format($endBalance, 0, ',', '.') }}</div>
            </div>
        </div>

        {{-- Transactions Table --}}
        <div class="overflow-x-auto w-full">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-400 whitespace-nowrap">
                <thead class="text-xs text-slate-500 uppercase bg-slate-50 dark:bg-slate-800/50 dark:text-slate-400 font-bold tracking-wider">
                    <tr>
                        <th class="px-6 py-4 rounded-tl-xl">{{ __('Tanggal') }}</th>
                        <th class="px-6 py-4">{{ __('Keterangan / Uraian') }}</th>
                        <th class="px-6 py-4">{{ __('Ref / Bukti') }}</th>
                        <th class="px-6 py-4 text-right">{{ __('Penerimaan') }}</th>
                        <th class="px-6 py-4 text-right">{{ __('Pengeluaran') }}</th>
                        <th class="px-6 py-4 text-right rounded-tr-xl">{{ __('Saldo') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <tr class="bg-emerald-50/30 dark:bg-emerald-950/20">
                        <td class="px-6 py-4 text-[11px] font-mono italic text-slate-500">
                            {{ \Carbon\Carbon::createFromDate($year, $month, 1)->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 font-bold text-slate-700 dark:text-slate-300">
                            {{ __('Saldo Pindahan') }}
                        </td>
                        <td class="px-6 py-4"></td>
                        <td class="px-6 py-4 text-right font-mono"></td>
                        <td class="px-6 py-4 text-right font-mono"></td>
                        <td class="px-6 py-4 text-right font-mono font-bold text-slate-900 dark:text-white">
                            Rp {{ number_format($startBalance, 0, ',', '.') }}
                        </td>
                    </tr>

                    @php $runningBalance = $startBalance; @endphp
                    
                    @forelse($transactions as $tx)
                        @php
                            if ($tx->type === 'income') {
                                $runningBalance += $tx->amount;
                            } else {
                                $runningBalance -= $tx->amount;
                            }
                        @endphp
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="px-6 py-4 text-[11px] font-mono font-bold text-slate-500">
                                {{ $tx->payment_date->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="font-semibold text-slate-800 dark:text-slate-200">
                                        @if($tx->type === 'income')
                                            @if($tx->billing)
                                                {{ $tx->billing->feeCategory?->name ?? 'Pemasukan' }} - {{ $tx->billing->student?->name ?? 'Siswa' }}
                                            @else
                                                {{ $tx->feeCategory?->name ?? 'Pemasukan Global/Saldo Awal' }}
                                            @endif
                                        @else
                                            {{ $tx->budgetItem?->name ?? 'Pengeluaran' }} 
                                        @endif
                                    </span>
                                    @if($tx->notes)
                                        <span class="text-[10px] text-slate-400 mt-0.5 truncate max-w-xs">{{ $tx->notes }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-xs font-mono text-slate-500">
                                {{ $tx->reference_number ?: '-' }}
                            </td>
                            <td class="px-6 py-4 text-right font-mono text-emerald-600 font-medium">
                                {{ $tx->type === 'income' ? number_format($tx->amount, 0, ',', '.') : '-' }}
                            </td>
                            <td class="px-6 py-4 text-right font-mono text-rose-600 font-medium">
                                {{ $tx->type === 'expense' ? number_format($tx->amount, 0, ',', '.') : '-' }}
                            </td>
                            <td class="px-6 py-4 text-right font-mono font-bold text-slate-900 dark:text-white">
                                {{ number_format($runningBalance, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400 italic">
                                {{ __('Tidak ada transaksi pada bulan ini.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
