<div class="p-6 space-y-6 text-slate-900 dark:text-white pb-24 md:pb-6">
    @if (session('success'))
        <x-ui.alert :title="__('Sukses')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100" dismissible>
            {{ session('success') }}
        </x-ui.alert>
    @endif

    <x-ui.header :title="__('Potongan & Beasiswa')" :subtitle="__('Kelola variasi biaya, diskon khusus, dan beasiswa untuk siswa tertentu.')" separator>
        <x-slot:actions>
            <x-ui.button :label="__('Tambah Data Potongan')" icon="o-plus" class="btn-primary" wire:click="create" />
        </x-slot:actions>
    </x-ui.header>

    <x-ui.card shadow padding="false">
        <x-ui.table 
            :headers="[
                ['key' => 'student_name', 'label' => __('Nama Siswa')],
                ['key' => 'category', 'label' => __('Kategori Biaya')],
                ['key' => 'details', 'label' => __('Detail Potongan')],
                ['key' => 'amount_label', 'label' => __('Nilai Potongan'), 'class' => 'text-right'],
                ['key' => 'actions', 'label' => __('Aksi'), 'class' => 'text-right']
            ]" 
            :rows="$discounts"
        >
            @scope('cell_student_name', $discount)
                <div class="flex flex-col">
                    <span class="font-bold text-slate-900 dark:text-white">{{ $discount->student?->name ?? __('Siswa Dihapus') }}</span>
                    <span class="text-[10px] text-slate-400 font-mono tracking-tighter">{{ $discount->student?->email ?? '-' }}</span>
                </div>
            @endscope

            @scope('cell_category', $discount)
                @if($discount->feeCategory)
                    <x-ui.badge :label="$discount->feeCategory->name" class="bg-indigo-50 text-indigo-600 border-none text-[8px] font-black italic shadow-sm" />
                @else
                    <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest bg-slate-100 px-2 py-0.5 rounded italic">{{ __('Semua Biaya') }}</span>
                @endif
            @endscope

            @scope('cell_details', $discount)
                <div class="flex flex-col">
                    <span class="font-bold text-xs text-slate-700 dark:text-slate-300">{{ $discount->name }}</span>
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ $discount->discount_type === 'percentage' ? __('Persentase (%)') : __('Nominal Tetap (Rp)') }}</span>
                </div>
            @endscope

            @scope('cell_amount_label', $discount)
                @if($discount->discount_type === 'percentage')
                    <span class="font-mono text-sm font-black text-indigo-600 italic bg-indigo-50 px-2 py-0.5 rounded-lg ring-1 ring-indigo-100 shadow-sm">
                        {{ $discount->amount }}%
                    </span>
                @else
                    <span class="font-mono text-sm font-black text-emerald-600 italic bg-emerald-50 px-2 py-0.5 rounded-lg ring-1 ring-emerald-100 shadow-sm">
                        Rp {{ number_format($discount->amount, 0, ',', '.') }}
                    </span>
                @endif
            @endscope

            @scope('cell_actions', $discount)
                <div class="flex justify-end gap-2">
                    <x-ui.button icon="o-pencil-square" wire:click="edit({{ $discount->id }})" class="btn-ghost btn-sm text-slate-400 hover:text-primary transition-colors" />
                    <x-ui.button icon="o-trash" wire:click="delete({{ $discount->id }})" wire:confirm="{{ __('Hapus pengaturan diskon ini?') }}" class="btn-ghost btn-sm text-slate-400 hover:text-rose-600 transition-colors" />
                </div>
            @endscope
        </x-ui.table>

        @if($discounts->isEmpty())
            <div class="py-12 text-center text-slate-400 italic text-sm">
                {{ __('Belum ada data potongan/beasiswa yang terdaftar.') }}
            </div>
        @endif

        <div class="p-4 border-t border-slate-100 dark:border-slate-800">
            {{ $discounts->links() }}
        </div>
    </x-ui.card>

    {{-- Add/Edit Modal --}}
    <x-ui.modal wire:model="discountModal">
        <x-ui.header :title="$editing ? __('Edit Potongan') : __('Tambah Potongan')" :subtitle="__('Tentukan rincian potongan atau beasiswa bagi siswa terpilih.')" separator />

        <div class="space-y-6">
            <x-ui.select wire:model="student_id" :label="__('Pilih Siswa')" :placeholder="__('Cari atau pilih nama siswa')" :options="$students" required />
            
            <x-ui.select 
                wire:model="fee_category_id" 
                :label="__('Target Kategori Biaya (Opsional)')" 
                :placeholder="__('Berlaku untuk semua jenis biaya')" 
                :options="collect($categories)->map(fn($c) => ['id' => $c->id, 'name' => $c->name . ($c->level ? ' (' . $c->level->name . ')' : ' (Umum)')])->toArray()" 
            />
            
            <x-ui.input wire:model="name" :label="__('Nama Program/Beasiswa')" :placeholder="__('Contoh: Beasiswa Tahfidz, Anak Karyawan, Prestasi Futsal')" required />
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-4 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-800">
                <x-ui.select 
                    wire:model="discount_type" 
                    :label="__('Metode Potongan')" 
                    :options="[['id' => 'fixed', 'name' => __('Nominal Tetap (Rp)')], ['id' => 'percentage', 'name' => __('Persentase (%)')]]" 
                    required
                />
                <x-ui.input wire:model="amount" type="number" :label="__('Besaran Nilai')" step="1" required />
            </div>
            
            <x-ui.alert icon="o-information-circle" class="bg-blue-50 text-blue-700 border-blue-100 mt-4 font-medium text-[10px]">
                {{ __('Diskon ini akan otomatis diperhitungkan sistem saat melakukan proses generate tagihan bulanan atau tunggal.') }}
            </x-ui.alert>
        </div>

        <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-slate-100 dark:border-slate-800">
            <x-ui.button :label="__('Batal')" wire:click="$set('discountModal', false)" />
            <x-ui.button :label="__('Simpan Perubahan')" class="btn-primary" wire:click="save" spinner="save" />
        </div>
    </x-ui.modal>
</div>
