<?php

use Livewire\Component;
use App\Models\BudgetCategory;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new class extends Component {
    use WithPagination, Toast;

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
            $this->success('Kategori anggaran berhasil diperbarui.');
        } else {
            BudgetCategory::create([
                'name' => $this->name,
                'code' => $this->code,
                'description' => $this->description,
                'is_active' => $this->is_active,
            ]);
            $this->success('Kategori anggaran berhasil ditambahkan.');
        }

        $this->categoryModal = false;
        $this->reset(['name', 'code', 'description', 'is_active', 'editing']);
    }

    public function delete(BudgetCategory $category): void
    {
        $category->delete();
        $this->success('Kategori anggaran berhasil dihapus.');
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-header title="Kategori Anggaran" subtitle="Kelola master kategori anggaran (POS) untuk RAB." separator>
        <x-slot:actions>
            <x-button label="Tambah Kategori" icon="o-plus" class="btn-primary" wire:click="createNew" />
        </x-slot:actions>
    </x-header>

    <div class="flex flex-col md:flex-row gap-4 mb-2 items-center justify-between">
        <x-input wire:model.live="search" icon="o-magnifying-glass" placeholder="Cari kategori..." class="w-full md:w-64" />
    </div>

    <div class="overflow-x-auto border rounded-xl border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
        <table class="table">
            <thead>
                <tr>
                    <th class="bg-base-200">Kode</th>
                    <th class="bg-base-200">Nama Kategori</th>
                    <th class="bg-base-200">Keterangan</th>
                    <th class="bg-base-200 text-center">Status</th>
                    <th class="bg-base-200 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $category)
                    <tr class="hover" wire:key="{{ $category->id }}">
                        <td class="font-mono text-sm font-medium">{{ $category->code }}</td>
                        <td class="font-bold">{{ $category->name }}</td>
                        <td class="opacity-70 text-sm italic">{{ $category->description ?? '-' }}</td>
                        <td class="text-center">
                            <x-badge :value="$category->is_active ? 'Aktif' : 'Non-Aktif'" class="{{ $category->is_active ? 'badge-success' : 'badge-error' }} badge-sm" />
                        </td>
                        <td class="text-right">
                            <div class="flex justify-end gap-1">
                                <x-button icon="o-pencil-square" wire:click="edit({{ $category->id }})" ghost sm />
                                <x-button icon="o-trash" class="text-error" wire:confirm="Hapus kategori ini?" wire:click="delete({{ $category->id }})" ghost sm />
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <div class="mt-4">
        {{ $categories->links() }}
    </div>

    <x-modal wire:model="categoryModal" class="backdrop-blur">
        <x-header :title="$editing ? 'Edit Kategori' : 'Tambah Kategori'" separator />

        <form wire:submit="save">
            <div class="grid grid-cols-1 gap-4 text-left">
                <x-input wire:model="code" label="Kode Kategori" placeholder="Contoh: ADM" required />
                <x-input wire:model="name" label="Nama Kategori" placeholder="Contoh: Belanja Administrasi" required />
                <x-textarea wire:model="description" label="Keterangan" />
                <x-checkbox wire:model="is_active" label="Status Aktif" />
            </div>

            <x-slot:actions>
                <x-button label="Batal" @click="$set('categoryModal', false)" />
                <x-button label="Simpan" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </form>
    </x-modal>
</div>
