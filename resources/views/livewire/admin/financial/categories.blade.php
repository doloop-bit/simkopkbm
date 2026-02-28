<div class="p-6 space-y-6 text-slate-900 dark:text-white pb-24 md:pb-6">
    @if (session('success'))
        <x-ui.alert :title="__('Sukses')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100" dismissible>
            {{ session('success') }}
        </x-ui.alert>
    @endif

    <x-ui.header :title="__('Kategori Biaya')" :subtitle="__('Daftar jenis biaya sekolah (SPP, Gedung, Pendaftaran, dll).')" separator>
        <x-slot:actions>
            <x-ui.button :label="__('Tambah Kategori Baru')" icon="o-plus" class="btn-primary" wire:click="create" />
        </x-slot:actions>
    </x-ui.header>

    <x-ui.card shadow padding="false">
        <x-ui.table 
            :headers="[
                ['key' => 'code', 'label' => __('Kode')],
                ['key' => 'name', 'label' => __('Nama Kategori')],
                ['key' => 'level_name', 'label' => __('Jenjang')],
                ['key' => 'amount', 'label' => __('Nominal Default'), 'class' => 'text-right'],
                ['key' => 'actions', 'label' => __('Aksi'), 'class' => 'text-right']
            ]" 
            :rows="$categories"
        >
            @scope('cell_code', $category)
                <span class="font-black font-mono text-[10px] bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded uppercase tracking-widest text-slate-500">
                    {{ $category->code }}
                </span>
            @endscope

            @scope('cell_name', $category)
                <div class="flex flex-col">
                    <span class="font-bold text-slate-900 dark:text-white">{{ $category->name }}</span>
                    @if($category->description)
                        <span class="text-[10px] text-slate-400 italic truncate max-w-xs font-medium">{{ $category->description }}</span>
                    @endif
                </div>
            @endscope

            @scope('cell_level_name', $category)
                @if($category->level)
                    <x-ui.badge :label="$category->level->name" class="bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400 border-none text-[8px] font-black" />
                @else
                    <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest">{{ __('Semua') }}</span>
                @endif
            @endscope

            @scope('cell_amount', $category)
                <span class="font-mono text-sm font-bold text-slate-700 dark:text-slate-300 italic">
                    Rp {{ number_format($category->default_amount, 0, ',', '.') }}
                </span>
            @endscope

            @scope('cell_actions', $category)
                <div class="flex justify-end gap-2">
                    <x-ui.button icon="o-pencil-square" wire:click="edit({{ $category->id }})" class="btn-ghost btn-sm text-slate-400 hover:text-primary transition-colors" />
                    <x-ui.button icon="o-trash" wire:click="confirmDelete({{ $category->id }})" class="btn-ghost btn-sm text-slate-400 hover:text-rose-600 transition-colors" />
                </div>
            @endscope
        </x-ui.table>

        @if($categories->isEmpty())
            <div class="py-12 text-center text-slate-400 italic text-sm">
                {{ __('Belum ada kategori biaya yang tersimpan.') }}
            </div>
        @endif

        <div class="p-4 border-t border-slate-100 dark:border-slate-800">
            {{ $categories->links() }}
        </div>
    </x-ui.card>

    {{-- Add/Edit Modal --}}
    <x-ui.modal wire:model="categoryModal">
        <x-ui.header :title="$editing ? __('Edit Kategori') : __('Tambah Kategori')" :subtitle="__('Deskripsikan kategori biaya baru secara detail.')" separator />

        <div class="space-y-6">
            <x-ui.input wire:model="code" :label="__('Kode Kategori')" :placeholder="__('Contoh: SPP-10, BLANJA-X')" required />
            <x-ui.input wire:model="name" :label="__('Nama Kategori')" :placeholder="__('Contoh: SPP Kelas 10, Uang Gedung')" required />
            
            <x-ui.select wire:model="level_id" :label="__('Jenjang (Opsional)')" :placeholder="__('Berlaku untuk semua jenjang')" :options="$levels" />

            <x-ui.input wire:model="default_amount" type="number" :label="__('Nominal Default (Rp)')" icon="o-banknotes" required />
            <x-ui.textarea wire:model="description" :label="__('Deskripsi Singkat')" rows="3" :placeholder="__('Jelaskan peruntukan biaya ini...')" />
        </div>

        <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-slate-100 dark:border-slate-800">
            <x-ui.button :label="__('Batal')" wire:click="$set('categoryModal', false)" />
            <x-ui.button :label="__('Simpan Kategori')" class="btn-primary" wire:click="save" spinner="save" />
        </div>
    </x-ui.modal>

    {{-- Delete Confirmation Modal --}}
    <x-ui.modal wire:model="deleteModal">
        <div class="flex flex-col items-center text-center py-6">
            <div class="w-16 h-16 bg-rose-50 dark:bg-rose-950/30 rounded-full flex items-center justify-center mb-6">
                <x-ui.icon name="o-trash" class="size-8 text-rose-600" />
            </div>
            <h3 class="text-xl font-black text-slate-900 dark:text-white mb-2">{{ __('Hapus Kategori Biaya?') }}</h3>
            <p class="text-xs text-slate-500 max-w-xs leading-relaxed font-medium">
                {{ __('Apakah Anda yakin ingin menghapus kategori biaya ini? Aksi ini tidak dapat dibatalkan dan sistem akan memvalidasi apakah kategori ini sudah digunakan dalam penagihan.') }}
            </p>
        </div>
        
        <div class="flex flex-col md:flex-row gap-3 mt-4">
            <x-ui.button :label="__('Batalkan')" wire:click="$set('deleteModal', false)" class="order-2 md:order-1 grow" />
            <x-ui.button :label="__('Ya, Hapus Permanen')" class="bg-rose-600 hover:bg-rose-700 text-white border-rose-700 order-1 md:order-2 grow" wire:click="delete" spinner="delete" />
        </div>
    </x-ui.modal>
</div>
