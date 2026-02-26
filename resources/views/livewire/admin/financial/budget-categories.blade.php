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
        $this->resetValidation();
        $this->dispatch('open-category-modal');
    }

    public function edit(BudgetCategory $category): void
    {
        $this->editing = $category;
        $this->name = $category->name;
        $this->code = $category->code;
        $this->description = $category->description;
        $this->is_active = $category->is_active;
        $this->dispatch('open-category-modal');
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
        } else {
            BudgetCategory::create([
                'name' => $this->name,
                'code' => $this->code,
                'description' => $this->description,
                'is_active' => $this->is_active,
            ]);
        }

        $this->dispatch('category-saved');
        $this->reset(['name', 'code', 'description', 'is_active', 'editing']);
    }

    public function delete(BudgetCategory $category): void
    {
        $category->delete();
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
        <div>
            <flux:heading size="xl">Kategori Anggaran</flux:heading>
            <flux:subheading>Kelola master kategori anggaran (POS) untuk RAB.</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="createNew">Tambah Kategori</flux:button>
    </div>

    <div class="flex flex-col md:flex-row gap-4 mb-6 items-center justify-between">
        <flux:input wire:model.live="search" icon="magnifying-glass" placeholder="Cari kategori..." class="w-full md:w-64" />
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead>
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Kode</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Nama Kategori</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Keterangan</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-zinc-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach($categories as $category)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $category->code }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ $category->name }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $category->description ?? '-' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <flux:badge variant="{{ $category->is_active ? 'success' : 'danger' }}" size="sm">
                                {{ $category->is_active ? 'Aktif' : 'Non-Aktif' }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="edit({{ $category->id }})" />
                            <flux:button size="sm" variant="ghost" icon="trash" class="text-red-500" wire:confirm="Hapus kategori ini?" wire:click="delete({{ $category->id }})" />
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <div class="mt-4">
        {{ $categories->links() }}
    </div>

    <flux:modal name="category-modal" class="max-w-md" @open-category-modal.window="$flux.modal('category-modal').show()" x-on:category-saved.window="$flux.modal('category-modal').close()">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editing ? 'Edit Kategori' : 'Tambah Kategori' }}</flux:heading>
            </div>

            <flux:input wire:model="code" label="Kode Kategori" placeholder="Contoh: ADM" required />
            <flux:input wire:model="name" label="Nama Kategori" placeholder="Contoh: Belanja Administrasi" required />
            <flux:textarea wire:model="description" label="Keterangan" />
            
            <flux:switch wire:model="is_active" label="Aktif" />

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" x-on:click="$flux.modal('category-modal').close()">Batal</flux:button>
                <flux:button type="submit" variant="primary">Simpan</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
