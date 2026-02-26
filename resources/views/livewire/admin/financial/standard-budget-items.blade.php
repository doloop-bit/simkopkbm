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
        $this->resetValidation();
        $this->dispatch('open-item-modal');
    }

    public function edit(StandardBudgetItem $item): void
    {
        $this->editing = $item;
        $this->budget_category_id = $item->budget_category_id;
        $this->name = $item->name;
        $this->unit = $item->unit;
        $this->default_price = $item->default_price;
        $this->is_active = $item->is_active;
        $this->dispatch('open-item-modal');
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
        } else {
            StandardBudgetItem::create($data);
        }

        $this->dispatch('item-saved');
        $this->reset(['budget_category_id', 'name', 'unit', 'default_price', 'is_active', 'editing']);
    }

    public function delete(StandardBudgetItem $item): void
    {
        $item->delete();
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
        <div>
            <flux:heading size="xl">Item Standar Anggaran</flux:heading>
            <flux:subheading>Kelola daftar item standar dan harga satuan untuk RAB.</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="createNew">Tambah Item</flux:button>
    </div>

    <div class="flex flex-col md:flex-row gap-4 mb-6 items-center justify-between">
        <div class="flex gap-2 w-full md:w-auto">
            <flux:input wire:model.live="search" icon="magnifying-glass" placeholder="Cari item..." class="w-full md:w-64" />
            <flux:select wire:model.live="category_filter" placeholder="Filter Kategori" class="w-full md:w-48">
                <option value="">Semua Kategori</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead>
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Nama Item</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Kategori</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Satuan</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-zinc-500 uppercase tracking-wider">Harga Standar</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-zinc-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach($items as $item)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $item->name }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">{{ $item->category->name }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">{{ $item->unit }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-zinc-900 dark:text-zinc-100">
                            {{ $item->default_price ? 'Rp ' . number_format($item->default_price, 0, ',', '.') : '-' }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <flux:badge variant="{{ $item->is_active ? 'success' : 'danger' }}" size="sm">
                                {{ $item->is_active ? 'Aktif' : 'Non-Aktif' }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="edit({{ $item->id }})" />
                            <flux:button size="sm" variant="ghost" icon="trash" class="text-red-500" wire:confirm="Hapus item ini?" wire:click="delete({{ $item->id }})" />
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <div class="mt-4">
        {{ $items->links() }}
    </div>

    <flux:modal name="item-modal" class="max-w-md" @open-item-modal.window="$flux.modal('item-modal').show()" x-on:item-saved.window="$flux.modal('item-modal').close()">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editing ? 'Edit Item' : 'Tambah Item' }}</flux:heading>
            </div>

            <flux:select wire:model="budget_category_id" label="Kategori" placeholder="Pilih Kategori" required>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </flux:select>

            <flux:input wire:model="name" label="Nama Item" placeholder="Contoh: Kertas A4" required />
            
            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="unit" label="Satuan" placeholder="Contoh: Rim" required />
                <flux:input wire:model="default_price" type="number" label="Harga Standar (Rp)" placeholder="0" />
            </div>
            
            <flux:switch wire:model="is_active" label="Aktif" />

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" x-on:click="$flux.modal('item-modal').close()">Batal</flux:button>
                <flux:button type="submit" variant="primary">Simpan</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
