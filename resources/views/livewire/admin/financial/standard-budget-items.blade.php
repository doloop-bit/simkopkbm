<?php

use Livewire\Component;
use App\Models\StandardBudgetItem;
use App\Models\BudgetCategory;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $category_filter = '';
    
    public $budget_category_id = '';
    public $name = '';
    public $unit = '';
    public $default_price = '';
    public $is_active = true;
    
    public ?StandardBudgetItem $editing = null;
    public bool $itemModal = false;

    public function with(): array
    {
        return [
            'items' => StandardBudgetItem::with('category')
                ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->when($this->category_filter, fn($q) => $q->where('budget_category_id', $this->category_filter))
                ->orderBy('name')
                ->paginate(10),
            'categories' => BudgetCategory::where('is_active', true)->orderBy('name')->get(),
        ];
    }

    public function createNew(): void
    {
        $this->reset(['budget_category_id', 'name', 'unit', 'default_price', 'is_active', 'editing']);
        $this->is_active = true;
        $this->resetValidation();
        $this->itemModal = true;
    }

    public function edit(StandardBudgetItem $item): void
    {
        $this->editing = $item;
        $this->budget_category_id = $item->budget_category_id;
        $this->name = $item->name;
        $this->unit = $item->unit;
        $this->default_price = $item->default_price;
        $this->is_active = $item->is_active;
        $this->itemModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'budget_category_id' => 'required|exists:budget_categories,id',
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'default_price' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $data = [
            'budget_category_id' => $this->budget_category_id,
            'name' => $this->name,
            'unit' => $this->unit,
            'default_price' => $this->default_price ?: null,
            'is_active' => $this->is_active,
        ];

        if ($this->editing) {
            $this->editing->update($data);
            session()->flash('success', 'Item standar anggaran berhasil diperbarui.');
        } else {
            StandardBudgetItem::create($data);
            session()->flash('success', 'Item standar anggaran berhasil ditambahkan.');
        }

        $this->itemModal = false;
        $this->reset(['budget_category_id', 'name', 'unit', 'default_price', 'is_active', 'editing']);
    }

    public function delete(StandardBudgetItem $item): void
    {
        $item->delete();
        session()->flash('success', 'Item standar anggaran berhasil dihapus.');
    }
}; ?>

<div class="p-6 space-y-6 text-slate-900 dark:text-white pb-24 md:pb-6">
    @if (session('success'))
        <x-ui.alert :title="__('Sukses')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100" dismissible>
            {{ session('success') }}
        </x-ui.alert>
    @endif

    <x-ui.header :title="__('Item Standar Anggaran')" :subtitle="__('Kelola daftar item standar dan estimasi harga satuan untuk mempermudah pembuatan RAB.')" separator>
        <x-slot:actions>
            <x-ui.button :label="__('Tambah Item Baru')" icon="o-plus" class="btn-primary" wire:click="createNew" />
        </x-slot:actions>
    </x-ui.header>

    <div class="flex flex-col md:flex-row gap-4">
        <div class="flex-1 max-w-md">
            <x-ui.input 
                wire:model.live.debounce.300ms="search" 
                :placeholder="__('Cari nama item...')" 
                icon="o-magnifying-glass" 
            />
        </div>
        <div class="w-full md:w-64">
            <x-ui.select 
                wire:model.live="category_filter" 
                :placeholder="__('Filter Kategori')" 
                :options="$categories" 
            />
        </div>
    </div>

    <x-ui.card shadow padding="false">
        <x-ui.table 
            :headers="[
                ['key' => 'name_label', 'label' => __('Nama Item')],
                ['key' => 'category_name', 'label' => __('Kategori')],
                ['key' => 'unit_label', 'label' => __('Satuan')],
                ['key' => 'price_label', 'label' => __('Harga Standar'), 'class' => 'text-right'],
                ['key' => 'status_label', 'label' => __('Status'), 'class' => 'text-center'],
                ['key' => 'actions', 'label' => __('Aksi'), 'class' => 'text-right']
            ]" 
            :rows="$items"
        >
            @scope('cell_name_label', $item)
                <span class="font-bold text-slate-900 dark:text-white">{{ $item->name }}</span>
            @endscope

            @scope('cell_category_name', $item)
                <x-ui.badge :label="$item->category->name" class="bg-indigo-50 text-indigo-600 border-none text-[8px] font-black italic shadow-sm" />
            @endscope

            @scope('cell_unit_label', $item)
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ $item->unit }}</span>
            @endscope

            @scope('cell_price_label', $item)
                <span class="font-mono text-sm font-black text-slate-700 dark:text-slate-300 italic tracking-tighter">
                    {{ $item->default_price ? 'Rp ' . number_format($item->default_price, 0, ',', '.') : '-' }}
                </span>
            @endscope

            @scope('cell_status_label', $item)
                <x-ui.badge 
                    :label="$item->is_active ? __('Aktif') : __('Non-Aktif')" 
                    class="{{ $item->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }} border-none text-[8px] font-black italic tracking-widest px-2 py-0.5" 
                />
            @endscope

            @scope('cell_actions', $item)
                <div class="flex justify-end gap-2">
                    <x-ui.button icon="o-pencil-square" wire:click="edit({{ $item->id }})" class="btn-ghost btn-sm text-slate-400 hover:text-primary transition-colors" />
                    <x-ui.button icon="o-trash" wire:click="delete({{ $item->id }})" wire:confirm="{{ __('Hapus item standar ini?') }}" class="btn-ghost btn-sm text-slate-400 hover:text-rose-600 transition-colors" />
                </div>
            @endscope
        </x-ui.table>

        @if($items->isEmpty())
            <div class="py-12 text-center text-slate-400 italic text-sm">
                {{ __('Tidak ada data item standar yang ditemukan.') }}
            </div>
        @endif

        <div class="p-4 border-t border-slate-100 dark:border-slate-800">
            {{ $items->links() }}
        </div>
    </x-ui.card>

    <x-ui.modal wire:model="itemModal">
        <x-ui.header :title="$editing ? __('Edit Item Standar') : __('Tambah Item Standar')" :subtitle="__('Kelola master item standar untuk referensi pengisian RAB.')" separator />

        <form wire:submit="save" class="space-y-6">
            <x-ui.select wire:model="budget_category_id" :label="__('Pilih Kategori POS')" :placeholder="__('Pilih Kategori')" :options="$categories" required />
            <x-ui.input wire:model="name" :label="__('Nama Item Deskriptif')" :placeholder="__('Contoh: Kertas A4 80gr')" required />
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-4 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-800">
                <x-ui.input wire:model="unit" :label="__('Satuan Ukuran')" :placeholder="__('Contoh: Rim, Box, Pack')" required />
                <x-ui.input wire:model="default_price" type="number" :label="__('Estimasi Harga Unit (Rp)')" placeholder="0" />
            </div>
            
            <x-ui.checkbox wire:model="is_active" :label="__('Item ini aktif & muncul di pencarian RAB')" />

            <div class="flex justify-end gap-3 pt-6 border-t border-slate-100 dark:border-slate-800">
                <x-ui.button :label="__('Batal')" wire:click="$set('itemModal', false)" />
                <x-ui.button :label="__('Simpan Perubahan')" type="submit" class="btn-primary" spinner="save" />
            </div>
        </form>
    </x-ui.modal>
</div>
