@if($tab === 'financial' && count($levelSummary) > 0)
    <x-ui.card shadow padding="false" class="border-none overflow-hidden ring-1 ring-slate-100 dark:ring-slate-800">
        <div class="p-6 border-b border-slate-50 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/50">
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
