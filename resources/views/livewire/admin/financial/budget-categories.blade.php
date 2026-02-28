<?php

use Livewire\Component;
use App\Models\BudgetCategory;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $name = '';
    public $code = '';
    public $description = '';
    public $is_active = true;
    public ?BudgetCategory $editing = null;
    public bool $categoryModal = false;

    public function with(): array
    {
        return [
            'categories' => BudgetCategory::when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%"))
                ->orderBy('name')
                ->paginate(10),
        ];
    }

    public function createNew(): void
    {
        $this->reset(['name', 'code', 'description', 'is_active', 'editing']);
        $this->is_active = true;
        $this->resetValidation();
        $this->categoryModal = true;
    }

    public function edit(BudgetCategory $category): void
    {
        $this->editing = $category;
        $this->name = $category->name;
        $this->code = $category->code;
        $this->description = $category->description;
        $this->is_active = $category->is_active;
        $this->categoryModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:budget_categories,code,' . ($this->editing?->id ?? 'NULL'),
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($this->editing) {
            $this->editing->update([
                'name' => $this->name,
                'code' => $this->code,
                'description' => $this->description,
                'is_active' => $this->is_active,
            ]);
            session()->flash('success', 'Kategori anggaran berhasil diperbarui.');
        } else {
            BudgetCategory::create([
                'name' => $this->name,
                'code' => $this->code,
                'description' => $this->description,
                'is_active' => $this->is_active,
            ]);
            session()->flash('success', 'Kategori anggaran berhasil ditambahkan.');
        }

        $this->categoryModal = false;
        $this->reset(['name', 'code', 'description', 'is_active', 'editing']);
    }

    public function delete(BudgetCategory $category): void
    {
        $category->delete();
        session()->flash('success', 'Kategori anggaran berhasil dihapus.');
    }
}; ?>

<div class="p-6 space-y-6 text-slate-900 dark:text-white pb-24 md:pb-6">
    @if (session('success'))
        <x-ui.alert :title="__('Sukses')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100" dismissible>
            {{ session('success') }}
        </x-ui.alert>
    @endif

    <x-ui.header :title="__('Kategori Anggaran')" :subtitle="__('Kelola master kategori anggaran (POS) untuk perencanaan RAB.')" separator>
        <x-slot:actions>
            <x-ui.button :label="__('Tambah Kategori Baru')" icon="o-plus" class="btn-primary" wire:click="createNew" />
        </x-slot:actions>
    </x-ui.header>

    <div class="max-w-md">
        <x-ui.input 
            wire:model.live.debounce.300ms="search" 
            :placeholder="__('Cari kode atau nama kategori...')" 
            icon="o-magnifying-glass" 
        />
    </div>

    <x-ui.card shadow padding="false">
        <x-ui.table 
            :headers="[
                ['key' => 'code_label', 'label' => __('Kode POS')],
                ['key' => 'name', 'label' => __('Nama Kategori')],
                ['key' => 'description', 'label' => __('Keterangan')],
                ['key' => 'status_label', 'label' => __('Status'), 'class' => 'text-center'],
                ['key' => 'actions', 'label' => __('Aksi'), 'class' => 'text-right']
            ]" 
            :rows="$categories"
        >
            @scope('cell_code_label', $category)
                <span class="font-mono text-xs font-black text-indigo-600 italic bg-indigo-50 px-2 py-0.5 rounded shadow-sm ring-1 ring-indigo-100">
                    {{ $category->code }}
                </span>
            @endscope

            @scope('cell_name', $category)
                <span class="font-bold text-slate-900 dark:text-white">{{ $category->name }}</span>
            @endscope

            @scope('cell_description', $category)
                <span class="text-xs text-slate-500 italic">{{ $category->description ?? '-' }}</span>
            @endscope

            @scope('cell_status_label', $category)
                <x-ui.badge 
                    :label="$category->is_active ? __('Aktif') : __('Non-Aktif')" 
                    class="{{ $category->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }} border-none text-[8px] font-black italic tracking-widest px-2 py-0.5" 
                />
            @endscope

            @scope('cell_actions', $category)
                <div class="flex justify-end gap-2">
                    <x-ui.button icon="o-pencil-square" wire:click="edit({{ $category->id }})" class="btn-ghost btn-sm text-slate-400 hover:text-primary transition-colors" />
                    <x-ui.button icon="o-trash" wire:click="delete({{ $category->id }})" wire:confirm="{{ __('Hapus kategori anggaran ini?') }}" class="btn-ghost btn-sm text-slate-400 hover:text-rose-600 transition-colors" />
                </div>
            @endscope
        </x-ui.table>

        @if($categories->isEmpty())
            <div class="py-12 text-center text-slate-400 italic text-sm">
                {{ __('Tidak ada data kategori anggaran yang ditemukan.') }}
            </div>
        @endif

        <div class="p-4 border-t border-slate-100 dark:border-slate-800">
            {{ $categories->links() }}
        </div>
    </x-ui.card>

    {{-- Add/Edit Modal --}}
    <x-ui.modal wire:model="categoryModal">
        <x-ui.header :title="$editing ? __('Edit Kategori') : __('Tambah Kategori')" :subtitle="__('Kelola rincian POS anggaran untuk pelaporan keuangan.')" separator />

        <form wire:submit="save" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="md:col-span-1">
                    <x-ui.input wire:model="code" :label="__('Kode Kategori')" :placeholder="__('Contoh: ADM')" required />
                </div>
                <div class="md:col-span-2">
                    <x-ui.input wire:model="name" :label="__('Nama Lengkap Kategori')" :placeholder="__('Contoh: Belanja Administrasi Umum')" required />
                </div>
            </div>

            <x-ui.textarea wire:model="description" :label="__('Keterangan Tambahan')" :placeholder="__('Opsional: Penjelasan rincian POS ini...')" rows="3" />
            
            <div class="p-4 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-800">
                <x-ui.checkbox wire:model="is_active" :label="__('Kategori ini aktif & bisa digunakan dalam RAB')" />
            </div>

            <div class="flex justify-end gap-3 pt-6 border-t border-slate-100 dark:border-slate-800">
                <x-ui.button :label="__('Batal')" wire:click="$set('categoryModal', false)" />
                <x-ui.button :label="__('Simpan Perubahan')" type="submit" class="btn-primary" spinner="save" />
            </div>
        </form>
    </x-ui.modal>
</div>
