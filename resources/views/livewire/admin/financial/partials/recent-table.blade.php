<div class="space-y-4">
    <x-ui.card shadow padding="false">
        <div class="p-5 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex items-center justify-between">
            <div class="text-xs font-bold uppercase text-slate-500 tracking-wider">{{ __('Riwayat Transaksi Terbaru') }}</div>
            <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider bg-white dark:bg-slate-800 px-3 py-1 rounded-full ring-1 ring-slate-100 dark:ring-slate-700 shadow-sm">{{ __('Real-time Update') }}</div>
        </div>

        <x-ui.table 
            :headers="[
                ['key' => 'payment_date', 'label' => __('Tanggal')],
                ['key' => 'type_label', 'label' => __('Jenis')],
                ['key' => 'description', 'label' => __('Keterangan')],
                ['key' => 'amount', 'label' => __('Nominal'), 'class' => 'text-right'],
                ['key' => 'actions', 'label' => '', 'class' => 'w-10']
            ]" 
            :rows="$recentTransactions"
        >
            @scope('cell_payment_date', $tx)
                <span class="text-[11px] font-mono font-bold text-slate-400 uppercase">{{ $tx->payment_date->format('d M Y') }}</span>
            @endscope

            @scope('cell_type_label', $tx)
                @if($tx->type === 'income')
                    <x-ui.badge :label="__('In')" class="bg-emerald-100 text-emerald-700 border-none text-[10px] font-bold px-2 py-0.5 tracking-wide" />
                @else
                    <x-ui.badge :label="__('Out')" class="bg-rose-100 text-rose-700 border-none text-[10px] font-bold px-2 py-0.5 tracking-wide" />
                @endif
            @endscope

            @scope('cell_description', $tx)
                <div class="flex flex-col">
                    @if($tx->type === 'income')
                        @if($tx->billing)
                            <span class="font-semibold text-slate-900 dark:text-white">{{ $tx->billing->student?->name ?? __('Siswa') }}</span>
                            <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ $tx->billing->feeCategory?->name ?? __('Tarif') }}</span>
                        @else
                            <span class="font-semibold text-slate-900 dark:text-white">{{ __('Pemasukan Global/Saldo Awal') }}</span>
                            <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ $tx->feeCategory?->name ?? __('Kategori') }}</span>
                        @endif
                    @else
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-slate-900 dark:text-white">{{ $tx->budgetItem?->name ?? __('RAB Item') }}</span>
                            @if($tx->attachment)
                                <div class="flex gap-1">
                                    @foreach((array)$tx->attachment as $file)
                                        <a href="{{ Storage::url($file) }}" target="_blank" class="p-1 bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-100 transition-colors" title="{{ __('Lihat Bukti') }}">
                                            <x-ui.icon name="o-paper-clip" class="size-3" />
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 truncate max-w-[150px]">{{ $tx->budgetPlan?->title ?? __('RAB Terpadu') }}</span>
                    @endif
                </div>
            @endscope

            @scope('cell_amount', $tx)
                <div class="font-mono text-xs font-bold {{ $tx->type === 'income' ? 'text-emerald-600' : 'text-rose-600' }}">
                    {{ $tx->type === 'income' ? '+' : '-' }} Rp {{ number_format($tx->amount, 0, ',', '.') }}
                </div>
            @endscope

            @scope('cell_actions', $tx)
                <div class="flex items-center justify-end gap-2">
                    @if(!auth()->user()->isYayasan())
                        <button wire:click="editTransaction({{ $tx->id }})" class="text-slate-400 hover:text-blue-500 transition-colors">
                            <x-ui.icon name="o-pencil" class="size-4" />
                        </button>
                        <button 
                            wire:click="deleteTransaction({{ $tx->id }})" 
                            wire:confirm="{{ __('Hapus transaksi ini? Tindakan ini akan menyesuaikan tagihan terkait.') }}"
                            class="text-slate-400 hover:text-rose-500 transition-colors"
                        >
                            <x-ui.icon name="o-trash" class="size-4" />
                        </button>
                    @else
                        <button wire:click="editTransaction({{ $tx->id }})" class="text-slate-400 hover:text-blue-500 transition-colors">
                            <x-ui.icon name="o-eye" class="size-4" />
                        </button>
                    @endif
                </div>
            @endscope
        </x-ui.table>
        
        @if($recentTransactions->isEmpty())
            <div class="py-12 text-center text-slate-400 italic text-sm">
                {{ __('Belum ada transaksi yang tercatat hari ini.') }}
            </div>
        @endif
    </x-ui.card>
</div>
