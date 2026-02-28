<div class="p-6 space-y-6 text-slate-900 dark:text-white pb-24 md:pb-6">
    @if (session('success'))
        <x-ui.alert :title="__('Sukses')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100" dismissible>
            {{ session('success') }}
        </x-ui.alert>
    @endif

    <div class="space-y-4">
        <x-ui.header :title="__('Transaksi Keuangan')" :subtitle="__('Catat Pemasukan (Pembayaran Siswa) dan Pengeluaran (Realisasi RAB).')" separator />
        
        <x-ui.alert :title="__('Tips Alur Keuangan')" icon="o-information-circle" class="bg-blue-50 text-blue-800 border-blue-100">
            <ol class="list-decimal pl-5 space-y-1 text-xs font-medium">
                <li>{{ __('Buat') }} <strong class="font-black underline decoration-blue-200 uppercase tracking-tighter">{{ __('Kategori Biaya') }}</strong> (SPP, Pendaftaran, dll).</li>
                <li>{{ __('Generate') }} <strong class="font-black underline decoration-blue-200 uppercase tracking-tighter">{{ __('Tagihan Siswa') }}</strong> {{ __('untuk menagih biaya ke siswa (opsional).') }}</li>
                <li>{{ __('Berikan') }} <strong class="font-black underline decoration-blue-200 uppercase tracking-tighter">{{ __('Potongan & Beasiswa') }}</strong> {{ __('jika diperlukan.') }}</li>
                <li>{{ __('Gunakan halaman ini untuk') }} <strong class="font-black underline decoration-blue-200 uppercase tracking-tighter">{{ __('Input Transaksi') }}</strong>.</li>
            </ol>
        </x-ui.alert>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-1 space-y-8">
            <x-ui.card shadow>
                <div class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-4">{{ __('Jenis Transaksi') }}</div>
                <div class="flex gap-2 p-1.5 bg-slate-100 dark:bg-slate-800 rounded-2xl">
                    <button 
                        wire:click="$set('type', 'income')"
                        class="flex-1 py-2.5 text-xs font-black rounded-xl transition-all flex items-center justify-center gap-2 {{ $type === 'income' ? 'bg-white dark:bg-slate-900 text-emerald-600 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200' }}"
                    >
                        <x-ui.icon name="o-arrow-down-tray" class="size-4" />
                        {{ __('Pemasukan') }}
                    </button>
                    <button 
                        wire:click="$set('type', 'expense')"
                        class="flex-1 py-2.5 text-xs font-black rounded-xl transition-all flex items-center justify-center gap-2 {{ $type === 'expense' ? 'bg-white dark:bg-slate-900 text-rose-600 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200' }}"
                    >
                        <x-ui.icon name="o-arrow-up-tray" class="size-4" />
                        {{ __('Pengeluaran') }}
                    </button>
                </div>
            </x-ui.card>

            @if($type === 'income')
                <x-ui.card shadow>
                    <div class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-6">{{ __('Detail Pemasukan') }}</div>
                    <div class="space-y-6">
                        <x-ui.select 
                            wire:model.live="fee_category_id" 
                            :label="__('Kategori Biaya')" 
                            :placeholder="__('Pilih Kategori')" 
                            :options="$feeCategories"
                        />
                        
                        <div class="relative">
                            <x-ui.input 
                                wire:model.live.debounce.300ms="student_search" 
                                :label="__('Cari Nama Siswa')"
                                :placeholder="__('Ketik minimal 3 huruf...')" 
                                icon="o-magnifying-glass" 
                                clearable
                                @clear="$wire.set('student_id', null); $wire.set('student_search', ''); $wire.checkExistingBilling()"
                            />

                            @if(count($students) > 0)
                                <div class="absolute z-50 w-full mt-2 bg-white dark:bg-slate-900 rounded-2xl shadow-2xl ring-1 ring-slate-200 dark:ring-slate-800 overflow-hidden divide-y divide-slate-50 dark:divide-slate-800">
                                    @foreach($students as $student)
                                        <button 
                                            wire:click="selectStudent({{ $student->id }})"
                                            class="w-full text-left px-5 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors group"
                                        >
                                            <div class="font-bold text-slate-900 dark:text-white group-hover:text-primary transition-colors">{{ $student->name }}</div>
                                            <div class="text-[10px] text-slate-400 font-mono tracking-tighter">{{ $student->email }}</div>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        @if($student_id && $fee_category_id)
                            @if($selectedBilling)
                                <div class="p-5 bg-emerald-50 dark:bg-emerald-950/20 rounded-2xl border border-emerald-100 dark:border-emerald-900/50 space-y-4 shadow-sm">
                                    <div class="flex justify-between items-center pb-2 border-b border-emerald-100 dark:border-emerald-900/30">
                                        <div class="text-[10px] font-black uppercase text-emerald-700 dark:text-emerald-400 tracking-widest">{{ __('Tagihan Ditemukan') }}</div>
                                        <x-ui.badge 
                                            :label="strtoupper($selectedBilling->status)" 
                                            class="bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300 text-[8px] font-black" 
                                        />
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex justify-between text-xs font-medium">
                                            <span class="text-slate-500">{{ __('Total Biaya:') }}</span>
                                            <span class="text-slate-900 dark:text-white font-mono tracking-tighter">Rp {{ number_format($selectedBilling->amount, 0, ',', '.') }}</span>
                                        </div>
                                        <div class="flex justify-between text-xs font-medium">
                                            <span class="text-slate-500">{{ __('Telah Dibayar:') }}</span>
                                            <span class="text-slate-900 dark:text-white font-mono tracking-tighter font-black">Rp {{ number_format($selectedBilling->paid_amount, 0, ',', '.') }}</span>
                                        </div>
                                        <div class="flex justify-between text-sm mt-3 pt-3 border-t border-emerald-100 dark:border-emerald-900/30 font-black">
                                            <span class="text-emerald-700 dark:text-emerald-400 uppercase tracking-tighter">{{ __('Sisa Tagihan:') }}</span>
                                            <span class="text-emerald-600 dark:text-emerald-300 font-mono text-base">Rp {{ number_format($selectedBilling->amount - $selectedBilling->paid_amount, 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <x-ui.alert icon="o-information-circle" class="bg-slate-50 text-slate-600 border-slate-100 dark:bg-slate-900/50 dark:border-slate-800">
                                    <div class="text-[10px] leading-relaxed italic font-medium">
                                        {{ __('Tidak ada tagihan tertunggak untuk kategori ini. Menyimpan transaksi akan otomatis membuatkan tagihan Lunas untuk siswa ini.') }}
                                    </div>
                                </x-ui.alert>
                            @endif
                        @endif
                    </div>
                </x-ui.card>
            @endif

            @if($type === 'expense')
                <x-ui.card shadow>
                    <div class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-6">{{ __('Detail RAB Pengeluaran') }}</div>
                    <div class="space-y-6">
                        <x-ui.select 
                            wire:model.live="budget_plan_id" 
                            :label="__('RAB Aktif')" 
                            :placeholder="__('Pilih Dokumen RAB')" 
                            :options="$activeBudgetPlans->map(fn($p) => ['id' => $p->id, 'name' => $p->title . ' (' . ($p->level?->name ?? __('Semua Tingkat')) . ')'])"
                        />
                        
                        @if($budget_plan_id)
                            <x-ui.select 
                                wire:model.live="budget_plan_item_id" 
                                :label="__('Item Anggaran')" 
                                :placeholder="__('Pilih Pos Anggaran')" 
                                :options="$budgetItems->map(fn($i) => ['id' => $i->id, 'name' => $i->name . ' (Anggaran: Rp ' . number_format($i->total, 0, ',', '.') . ')'])"
                            />
                        @endif
                    </div>
                </x-ui.card>
            @endif
        </div>

        <div class="lg:col-span-2 space-y-8">
            @if(($type === 'income' && $student_id && $fee_category_id) || ($type === 'expense' && $budget_plan_id && $budget_plan_item_id))
                <x-ui.card shadow class="{{ $type === 'income' ? 'bg-emerald-50/20 border-emerald-100' : 'bg-rose-50/20 border-rose-100' }}">
                    <x-ui.header :title="($type === 'income' ? __('Form Pemasukan') : __('Form Pengeluaran'))" separator />
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <x-ui.input wire:model="pay_amount" type="number" :label="__('Nominal Realisasi (Rp)')" icon="o-currency-dollar" required />
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
                        <x-ui.input wire:model="payment_date" type="date" :label="__('Tanggal Transaksi')" required />
                        <x-ui.input wire:model="reference_number" :label="__('Ref Transaksi (Opsional)')" :placeholder="__('No. Slip/Referensi')" />
                    </div>

                    <div class="mt-8">
                        <x-ui.textarea wire:model="notes" :label="__('Keterangan Tambahan')" rows="3" :placeholder="__('Catatan detail transaksi...')" />
                    </div>

                    <div class="flex justify-end pt-6 border-t border-slate-100 dark:border-slate-800 mt-6">
                        <x-ui.button 
                            :label="__('Simpan Record Transaksi')" 
                            icon="o-check" 
                            class="grow md:grow-0 {{ $type === 'income' ? 'btn-primary' : 'bg-rose-600 hover:bg-rose-700 text-white border-rose-700 shadow-rose-200' }}" 
                            wire:click="recordTransaction" 
                            spinner="recordTransaction"
                        />
                    </div>
                </x-ui.card>
            @else
                <div class="border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl p-16 text-center opacity-70 bg-slate-50/50 dark:bg-slate-950/20 flex flex-col items-center justify-center h-full min-h-[350px]">
                    <div class="w-16 h-16 bg-white dark:bg-slate-900 rounded-3xl shadow-xl flex items-center justify-center mb-6 ring-1 ring-slate-100 dark:ring-slate-800">
                        <x-ui.icon name="o-document-text" class="size-8 text-slate-300" />
                    </div>
                    <h3 class="text-lg font-black text-slate-800 dark:text-slate-200 mb-2">{{ __('Siap Mencatat Transaksi') }}</h3>
                    <p class="text-xs text-slate-400 max-w-xs leading-relaxed font-medium">
                        {{ __('Silakan pilih detail') }} <span class="font-black underline decoration-slate-200 uppercase tracking-tighter">{{ $type === 'income' ? __('Pemasukan') : __('RAB Pengeluaran') }}</span> {{ __('di panel sebelah kiri untuk memunculkan form transaksi.') }}
                    </p>
                </div>
            @endif

            <x-ui.card shadow padding="false">
                <div class="p-5 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex items-center justify-between">
                    <div class="text-[11px] font-black uppercase text-slate-400 tracking-widest">{{ __('Riwayat Transaksi Terbaru') }}</div>
                    <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest bg-white dark:bg-slate-800 px-3 py-1 rounded-full ring-1 ring-slate-100 dark:ring-slate-700 shadow-sm">{{ __('Real-time Update') }}</div>
                </div>

                <x-ui.table 
                    :headers="[
                        ['key' => 'payment_date', 'label' => __('Tanggal')],
                        ['key' => 'type_label', 'label' => __('Jenis')],
                        ['key' => 'description', 'label' => __('Keterangan')],
                        ['key' => 'amount', 'label' => __('Nominal'), 'class' => 'text-right']
                    ]" 
                    :rows="$recentTransactions"
                >
                    @scope('cell_payment_date', $tx)
                        <span class="text-[11px] font-mono font-bold text-slate-400 uppercase">{{ $tx->payment_date->format('d M Y') }}</span>
                    @endscope

                    @scope('cell_type_label', $tx)
                        @if($tx->type === 'income')
                            <x-ui.badge :label="__('In')" class="bg-emerald-100 text-emerald-700 border-none text-[8px] font-black px-1.5 py-0.5" />
                        @else
                            <x-ui.badge :label="__('Out')" class="bg-rose-100 text-rose-700 border-none text-[8px] font-black px-1.5 py-0.5" />
                        @endif
                    @endscope

                    @scope('cell_description', $tx)
                        <div class="flex flex-col">
                            @if($tx->type === 'income')
                                <span class="font-bold text-slate-900 dark:text-white">{{ $tx->billing?->student?->name ?? __('Siswa Tidak Diketahui') }}</span>
                                <span class="text-[9px] font-black uppercase tracking-widest text-slate-400">{{ $tx->billing?->feeCategory?->name ?? __('Tarif') }}</span>
                            @else
                                <span class="font-bold text-slate-900 dark:text-white">{{ $tx->budgetItem?->name ?? __('RAB Item') }}</span>
                                <span class="text-[9px] font-black uppercase tracking-widest text-slate-400 truncate max-w-[150px]">{{ $tx->budgetPlan?->title ?? __('RAB Terpadu') }}</span>
                            @endif
                        </div>
                    @endscope

                    @scope('cell_amount', $tx)
                        <div class="font-mono text-sm tracking-tighter font-black {{ $tx->type === 'income' ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $tx->type === 'income' ? '+' : '-' }} Rp {{ number_format($tx->amount, 0, ',', '.') }}
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
    </div>
</div>
