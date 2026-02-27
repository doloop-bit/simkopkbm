<?php

use Livewire\Component;
use App\Models\StandardBudgetItem;
use App\Models\BudgetCategory;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new class extends Component {
    use WithPagination, Toast;

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
            $this->success('Item standar anggaran berhasil diperbarui.');
        } else {
            StandardBudgetItem::create($data);
            $this->success('Item standar anggaran berhasil ditambahkan.');
        }

        $this->itemModal = false;
        $this->reset(['budget_category_id', 'name', 'unit', 'default_price', 'is_active', 'editing']);
    }

    public function delete(StandardBudgetItem $item): void
    {
        $item->delete();
        $this->success('Item standar anggaran berhasil dihapus.');
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-header title="Item Standar Anggaran" subtitle="Kelola daftar item standar dan harga satuan untuk RAB." separator>
        <x-slot:actions>
            <x-button label="Tambah Item" icon="o-plus" class="btn-primary" wire:click="createNew" />
        </x-slot:actions>
    </x-header>

    <div class="flex flex-col md:flex-row gap-4 mb-2 items-center justify-between">
        <div class="flex gap-2 w-full md:w-auto">
            <x-input wire:model.live="search" icon="o-magnifying-glass" placeholder="Cari item..." class="w-full md:w-64" />
            <x-select wire:model.live="category_filter" placeholder="Filter Kategori" :options="$categories" class="w-full md:w-48" />
        </div>
    </div>

    <div class="overflow-x-auto border rounded-xl border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
        <table class="table">
            <thead>
                <tr>
                    <th class="bg-base-200">Nama Item</th>
                    <th class="bg-base-200">Kategori</th>
                    <th class="bg-base-200">Satuan</th>
                    <th class="bg-base-200 text-right">Harga Standar</th>
                    <th class="bg-base-200 text-center">Status</th>
                    <th class="bg-base-200 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    <tr class="hover" wire:key="{{ $item->id }}">
                        <td class="font-bold whitespace-nowrap">{{ $item->name }}</td>
                        <td class="opacity-70">{{ $item->category->name }}</td>
                        <td class="opacity-70">{{ $item->unit }}</td>
                        <td class="text-right font-mono">
                            {{ $item->default_price ? 'Rp ' . number_format($item->default_price, 0, ',', '.') : '-' }}
                        </td>
                        <td class="text-center">
                            <x-badge :value="$item->is_active ? 'Aktif' : 'Non-Aktif'" class="{{ $item->is_active ? 'badge-success' : 'badge-error' }} badge-sm" />
                        </td>
                        <td class="text-right">
                            <div class="flex justify-end gap-1">
                                <x-button icon="o-pencil-square" wire:click="edit({{ $item->id }})" ghost sm />
                                <x-button icon="o-trash" class="text-error" wire:confirm="Hapus item ini?" wire:click="delete({{ $item->id }})" ghost sm />
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <div class="mt-4">
        {{ $items->links() }}
    </div>

    <x-modal wire:model="itemModal" class="backdrop-blur">
        <x-header :title="$editing ? 'Edit Item' : 'Tambah Item'" separator />

        <form wire:submit="save">
            <div class="grid grid-cols-1 gap-4 text-left">
                <x-select wire:model="budget_category_id" label="Kategori" placeholder="Pilih Kategori" :options="$categories" required />
                <x-input wire:model="name" label="Nama Item" placeholder="Contoh: Kertas A4" required />
                
                <div class="grid grid-cols-2 gap-4">
                    <x-input wire:model="unit" label="Satuan" placeholder="Contoh: Rim" required />
                    <x-input wire:model="default_price" type="number" label="Harga Standar (Rp)" placeholder="0" />
                </div>
                
                <x-checkbox wire:model="is_active" label="Status Aktif" />
            </div>

            <x-slot:actions>
                <x-button label="Batal" @click="$set('itemModal', false)" />
                <x-button label="Simpan" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </form>
    </x-modal>
</div>
