<div class="p-6 space-y-6 text-slate-900 dark:text-white pb-24 md:pb-6">
    @if (session('success'))
        <x-ui.alert :title="__('Sukses')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100" dismissible>
            {{ session('success') }}
        </x-ui.alert>
    @endif

    <x-ui.header :title="__('Transaksi Pembayaran')" :subtitle="__('Catat pembayaran biaya sekolah dari siswa secara mandiri.')" separator />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-1 space-y-8">
            <x-ui.card shadow>
                <div class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-6">{{ __('Cari Siswa') }}</div>
                <div class="relative">
                    <x-ui.input 
                        wire:model.live.debounce.300ms="search" 
                        :placeholder="__('Ketik nama siswa...')" 
                        icon="o-magnifying-glass" 
                        clearable
                        @clear="$wire.set('student_id', null); $wire.set('search', '')"
                    />
                </div>

                @if(count($students) > 0)
                    <div class="mt-2 bg-white dark:bg-slate-900 rounded-2xl shadow-2xl ring-1 ring-slate-200 dark:ring-slate-800 overflow-hidden divide-y divide-slate-50 dark:divide-slate-800 absolute z-50 w-[calc(100%-3rem)]">
                        @foreach($students as $student)
                            <button 
                                wire:click="selectStudent({{ $student->id }})"
                                class="w-full text-left px-5 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/10 transition group"
                            >
                                <div class="font-bold text-slate-900 dark:text-white group-hover:text-primary transition-colors">{{ $student->name }}</div>
                                <div class="text-[10px] text-slate-400 font-mono tracking-tighter">{{ $student->email }}</div>
                            </button>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>

            @if($student_id)
                <x-ui.card shadow padding="false">
                    <div class="p-5 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                        <div class="text-[10px] font-black uppercase text-slate-400 tracking-widest">{{ __('Tagihan Terbuka') }}</div>
                    </div>
                    <div class="p-2 space-y-2">
                        @forelse($billings as $billing)
                            <button 
                                wire:click="selectBilling({{ $billing->id }})"
                                class="w-full text-left p-4 rounded-xl transition border-2 flex flex-col gap-3 group {{ $selectedBilling?->id === $billing->id ? 'bg-primary/5 border-primary/20 ring-1 ring-primary/10' : 'bg-transparent border-transparent hover:bg-slate-50 dark:hover:bg-slate-800/50' }}"
                            >
                                <div class="flex justify-between items-start">
                                    <div class="flex flex-col">
                                        <span class="font-black text-xs text-slate-900 dark:text-white uppercase tracking-tight group-hover:text-primary transition-colors">{{ $billing->feeCategory?->name ?? __('Kategori Dihapus') }}</span>
                                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ $billing->month ?? __('Sekali Bayar') }}</span>
                                    </div>
                                    <x-ui.badge :label="strtoupper($billing->status)" class="{{ $billing->status === 'partial' ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700' }} border-none text-[8px] font-black px-1.5 py-0.5" />
                                </div>
                                <div class="flex justify-between items-end pt-2 border-t border-slate-100 dark:border-slate-800">
                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ __('Sisa:') }}</span>
                                    <span class="font-mono text-sm font-black text-slate-900 dark:text-white tracking-tighter italic">Rp {{ number_format($billing->amount - $billing->paid_amount, 0, ',', '.') }}</span>
                                </div>
                            </button>
                        @empty
                            <div class="py-12 text-center text-slate-300 italic text-xs">
                                {{ __('Lengkap! Tidak ada tunggakan.') }}
                            </div>
                        @endforelse
                    </div>
                </x-ui.card>
            @endif
        </div>

        <div class="lg:col-span-2 space-y-8">
            @if($selectedBilling)
                <x-ui.card shadow class="bg-primary/5 border-primary/20 border-2">
                    <x-ui.header :title="__('Form Pembayaran')" separator />
                    
                    <div class="grid grid-cols-2 gap-8 mb-8">
                        <div class="flex flex-col gap-1">
                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ __('Siswa') }}</span>
                            <span class="font-black text-xl text-slate-900 dark:text-white tracking-tight">{{ $selectedBilling->student?->name ?? __('Siswa Dihapus') }}</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ __('Kategori Biaya') }}</span>
                            <span class="font-black text-xl text-slate-900 dark:text-white tracking-tight">{{ $selectedBilling->feeCategory?->name ?? __('Kategori Dihapus') }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <x-ui.input wire:model="pay_amount" type="number" :label="__('Nominal Pembayaran (Rp)')" icon="o-banknotes" required />
                        <x-ui.select 
                            wire:model="payment_method" 
                            :label="__('Metode Pembayaran')" 
                            :options="[
                                ['id' => 'cash', 'name' => __('Tunai (Cash)')], 
                                ['id' => 'transfer', 'name' => __('Transfer Bank')], 
                                ['id' => 'other', 'name' => __('Lainnya')]
                            ]" 
                            required
                        />
                        <x-ui.input wire:model="payment_date" type="date" :label="__('Tanggal Pembayaran')" required />
                        <x-ui.input wire:model="reference_number" :label="__('Ref Transaksi (Opsional)')" :placeholder="__('No. Slip/Referensi')" />
                    </div>

                    <div class="mt-8">
                        <x-ui.textarea wire:model="notes" :label="__('Catatan')" rows="3" :placeholder="__('Contoh: Titipan orang tua, lunas semester 1, dll...')" />
                    </div>

                    <div class="flex justify-end pt-6 border-t border-slate-100 dark:border-slate-800 mt-8">
                        <x-ui.button :label="__('Simpan Record Pembayaran')" icon="o-check" class="btn-primary grow md:grow-0" wire:click="recordPayment" spinner="recordPayment" />
                    </div>
                </x-ui.card>
            @else
                <div class="border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-3xl p-16 text-center opacity-70 bg-slate-50/50 dark:bg-slate-950/20 flex flex-col items-center justify-center h-full min-h-[350px]">
                    <div class="w-16 h-16 bg-white dark:bg-slate-900 rounded-full shadow-xl flex items-center justify-center mb-6 ring-1 ring-slate-100 dark:ring-slate-800">
                        <x-ui.icon name="o-banknotes" class="size-8 text-slate-300" />
                    </div>
                    <h3 class="text-xl font-black text-slate-800 dark:text-white mb-2">{{ __('Mulai Pencatatan') }}</h3>
                    <p class="text-xs text-slate-400 max-w-xs leading-relaxed font-medium">
                        {{ __('Pilih siswa dan klik salah satu tagihannya di panel kiri untuk membuka form pembayaran resmi.') }}
                    </p>
                </div>
            @endif

            <x-ui.card shadow padding="false">
                <div class="p-5 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex items-center justify-between">
                    <div class="text-[11px] font-black uppercase text-slate-400 tracking-widest">{{ __('Riwayat Pembayaran Terbaru') }}</div>
                    <x-ui.icon name="o-clock" class="size-4 text-slate-300" />
                </div>
                
                <x-ui.table 
                    :headers="[
                        ['key' => 'payment_date', 'label' => __('Tanggal')],
                        ['key' => 'student', 'label' => __('Siswa')],
                        ['key' => 'category', 'label' => __('Biaya')],
                        ['key' => 'amount_label', 'label' => __('Nominal'), 'class' => 'text-right']
                    ]" 
                    :rows="$recentTransactions"
                >
                    @scope('cell_payment_date', $tx)
                        <span class="text-[11px] font-mono font-bold text-slate-400 uppercase tracking-tighter">{{ $tx->payment_date->format('d/m/Y') }}</span>
                    @endscope

                    @scope('cell_student', $tx)
                        <span class="font-bold text-slate-900 dark:text-white">{{ $tx->billing?->student?->name ?? __('Siswa Dihapus') }}</span>
                    @endscope

                    @scope('cell_category', $tx)
                        <x-ui.badge :label="$tx->billing?->feeCategory?->name ?? __('Kategori Dihapus')" class="bg-slate-100 text-slate-600 border-none text-[8px] font-black px-1.5 py-0.5" />
                    @endscope

                    @scope('cell_amount_label', $tx)
                        <div class="font-mono text-sm tracking-tighter font-black text-emerald-600 italic">
                            Rp {{ number_format($tx->amount, 0, ',', '.') }}
                        </div>
                    @endscope
                </x-ui.table>

                @if($recentTransactions->isEmpty())
                    <div class="py-12 text-center text-slate-400 italic text-sm">
                        {{ __('Belum ada pembayaran yang tercatat hari ini.') }}
                    </div>
                @endif
            </x-ui.card>
        </div>
    </div>
</div>
