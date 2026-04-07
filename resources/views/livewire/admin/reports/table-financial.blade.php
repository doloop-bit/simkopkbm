@if($tab === 'financial')
    <x-ui.card shadow padding="false" class="border-none ring-1 ring-slate-100 dark:ring-slate-800 overflow-hidden">
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
    </x-ui.card>
@endif
