<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
    <button 
        type="button"
        wire:click="switchType('income')"
        wire:loading.attr="disabled"
        class="group relative overflow-hidden p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all text-left"
    >
        <div class="flex items-center gap-4">
            <div class="size-12 bg-emerald-50 dark:bg-emerald-950/30 rounded-xl flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform">
                <div wire:loading wire:target="switchType('income')">
                    <x-ui.icon name="o-arrow-path" class="size-6 animate-spin" />
                </div>
                <div wire:loading.remove wire:target="switchType('income')">
                    <x-ui.icon name="o-arrow-down-tray" class="size-6" />
                </div>
            </div>
            <div>
                <h3 class="text-base font-black text-slate-900 dark:text-white uppercase tracking-tighter">{{ __('Catat Pemasukan') }}</h3>
                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest">{{ __('Pembayaran SPP, dll.') }}</p>
            </div>
        </div>
    </button>

    <button 
        type="button"
        wire:click="switchType('expense')"
        wire:loading.attr="disabled"
        class="group relative overflow-hidden p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all text-left"
    >
        <div class="flex items-center gap-4">
            <div class="size-12 bg-rose-50 dark:bg-rose-950/30 rounded-xl flex items-center justify-center text-rose-600 group-hover:scale-110 transition-transform">
                <div wire:loading wire:target="switchType('expense')">
                    <x-ui.icon name="o-arrow-path" class="size-6 animate-spin" />
                </div>
                <div wire:loading.remove wire:target="switchType('expense')">
                    <x-ui.icon name="o-arrow-up-tray" class="size-6" />
                </div>
            </div>
            <div>
                <h3 class="text-base font-black text-slate-900 dark:text-white uppercase tracking-tighter">{{ __('Catat Pengeluaran') }}</h3>
                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest">{{ __('Realisasi RAB, dll.') }}</p>
            </div>
        </div>
    </button>
</div>
