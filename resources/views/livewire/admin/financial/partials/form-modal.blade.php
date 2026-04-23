<form wire:submit="recordTransaction">
    <x-ui.modal wire:model="recordModal" :title="($editingTransactionId ? __('Koreksi Transaksi') : ($type === 'income' ? __('Catat Pemasukan Baru') : __('Catat Pengeluaran Baru')))" maxWidth="max-w-4xl">
        <div class="space-y-8">
            @if ($errors->any())
                <x-ui.alert :title="__('Perhatian')" icon="o-exclamation-triangle" class="bg-rose-50 text-rose-800 border-rose-100">
                    <ul class="text-xs font-semibold list-disc pl-5 mt-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-ui.alert>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-start">
                {{-- SELECTION COLUMN --}}
                <div class="space-y-6">
                    <div class="text-[10px] font-black uppercase text-slate-400 tracking-widest">{{ __('Destinasi Keuangan') }}</div>
                    
                    @if($type === 'income')
                        <x-ui.checkbox wire:model.live="is_global" :label="__('Saldo Awal / Pemasukan Global (Tanpa Siswa)')" class="text-[10px] font-bold text-slate-700 dark:text-slate-300 mb-4" />
                        
                        <x-ui.select 
                            wire:model.live="fee_category_id" 
                            :label="__('Kategori Biaya')" 
                            :placeholder="__('Pilih Kategori')" 
                            :options="$feeCategories"
                        />
                        
                        @if(!$is_global)
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
                                                type="button"
                                                wire:click="selectStudent({{ $student->id }})"
                                                class="w-full text-left px-5 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors group"
                                            >
                                                <div class="font-bold text-slate-900 dark:text-white group-hover:text-primary transition-colors text-xs">{{ $student->name }}</div>
                                                <div class="text-[9px] text-slate-400 font-mono tracking-tighter">{{ $student->email }}</div>
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            @if($student_id && $fee_category_id && $selectedBilling)
                                <div class="p-4 bg-emerald-50 dark:bg-emerald-950/20 rounded-xl border border-emerald-100 dark:border-emerald-900/50 space-y-3">
                                    <div class="flex justify-between items-center text-[10px] font-bold uppercase text-emerald-700 tracking-wider">
                                        <span>{{ __('Tagihan Aktif') }}</span>
                                        <x-ui.badge :label="strtoupper($selectedBilling->status)" class="text-[8px]" />
                                    </div>
                                    <div class="flex justify-between text-xs font-black">
                                        <span class="text-slate-500 uppercase tracking-tighter">{{ __('Sisa Tagihan:') }}</span>
                                        <span class="text-emerald-600 dark:text-emerald-300 font-mono text-base">Rp {{ number_format($selectedBilling->amount - $selectedBilling->paid_amount, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            @endif
                        @endif
                    @else
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
                    @endif
                </div>

                {{-- TRANSACTION DETAIL COLUMN --}}
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <x-ui.input wire:model="pay_amount" type="number" :label="__('Nominal Bayar (Rp)')" icon="o-banknotes" required />
                        <x-ui.input wire:model="adjustment_amount" type="number" :label="__('Adjusment (+/-)')" icon="o-adjustments-horizontal" :placeholder="__('Contoh: -500')" />
                    </div>
                    
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
                    <x-ui.textarea wire:model="notes" :label="__('Catatan')" rows="1" :placeholder="__('Detail tambahan...')" />
                </div>
            </div>
        </div>

        {{-- COMPACT ATTACHMENTS --}}
        <div class="border-t border-slate-100 dark:border-slate-800 pt-6 space-y-3">
            <div class="flex items-center justify-between">
                <div class="text-[10px] font-black uppercase text-slate-400 tracking-widest">{{ __('Lampiran Bukti / Kwitansi') }}</div>
                <div wire:loading wire:target="attachments" class="text-[10px] font-bold text-emerald-600 animate-pulse">{{ __('Mengunggah...') }}</div>
            </div>
            
            <div class="flex flex-wrap items-center gap-3">
                @foreach($attachments as $index => $file)
                    @if($file)
                        <div class="relative size-16 rounded-xl overflow-hidden bg-slate-100 border border-slate-200 group">
                            @php
                                $isImage = false;
                                try {
                                    $isImage = in_array($file->getClientOriginalExtension(), ['jpg', 'jpeg', 'png']);
                                } catch(\Exception $e) {}
                            @endphp
                            @if($isImage)
                                <img src="{{ $file->temporaryUrl() }}" class="w-full h-full object-cover">
                            @else
                                <div class="flex items-center justify-center h-full bg-slate-50">
                                    <x-ui.icon name="o-document" class="size-6 text-slate-400" />
                                </div>
                            @endif
                            <button type="button" @click="$wire.set('attachments.{{ $index }}', null)" class="absolute inset-0 bg-rose-500/80 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <x-ui.icon name="o-trash" class="size-4" />
                            </button>
                        </div>
                    @endif
                @endforeach

                @if(count($attachments) < 5)
                    <label class="size-16 rounded-xl border-2 border-dashed border-slate-200 dark:border-slate-800 flex flex-col items-center justify-center cursor-pointer hover:bg-slate-50 transition-colors group">
                        <x-ui.icon name="o-camera" class="size-5 text-slate-400 group-hover:text-primary transition-colors" />
                        <input wire:model="attachments" type="file" class="hidden" accept="image/*,.pdf" multiple />
                    </label>
                @endif

                @if($editingTransactionId)
                    <div class="text-[9px] font-bold text-slate-400 italic max-w-xs">{{ __('File baru akan ditambahkan ke lampiran yang sudah ada.') }}</div>
                @endif
            </div>
            @error('attachments') <span class="text-xs text-rose-500 font-bold block">{{ $message }}</span> @enderror
        </div>

        <x-slot name="actions">
            <x-ui.button :label="__('Tutup')" @click="show = false; $wire.closeModal()" class="btn-ghost" />
            @if(!auth()->user()->isYayasan())
                <x-ui.button 
                    :label="$editingTransactionId ? __('Simpan Koreksi') : __('Simpan Transaksi')" 
                    type="submit"
                    icon="o-check-circle" 
                    class="{{ $type === 'income' ? 'btn-primary' : 'bg-rose-600 hover:bg-rose-700 text-white border-rose-700' }}" 
                    spinner="recordTransaction"
                />
            @endif
        </x-slot>
    </x-ui.modal>
</form>
