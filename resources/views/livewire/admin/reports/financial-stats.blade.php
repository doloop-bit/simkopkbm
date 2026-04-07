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
