<?php

declare(strict_types=1);

use App\Models\FeeCategory;
use App\Models\Level;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new #[Layout('components.admin.layouts.app')] class extends Component {
    use WithPagination, Toast;

    public string $name = '';
    public string $code = '';
    public string $description = '';
    public float $default_amount = 0;
    public ?int $level_id = null;

    public ?FeeCategory $editing = null;
    public bool $categoryModal = false;
    public bool $deleteModal = false;
    public ?int $deletingId = null;

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:15|unique:fee_categories,code,' . ($this->editing?->id ?? 'NULL'),
            'description' => 'nullable|string',
            'default_amount' => 'required|numeric|min:0',
            'level_id' => 'nullable|exists:levels,id',
        ];
    }

    public function create(): void
    {
        $this->reset(['name', 'code', 'description', 'default_amount', 'level_id', 'editing']);
        $this->categoryModal = true;
    }

    public function edit(FeeCategory $category): void
    {
        $this->editing = $category;
        $this->name = $category->name;
        $this->code = $category->code;
        $this->description = $category->description ?? '';
        $this->default_amount = (float) $category->default_amount;
        $this->level_id = $category->level_id;
        $this->categoryModal = true;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->editing) {
            $this->editing->update([
                'name' => $this->name,
                'code' => $this->code,
                'description' => $this->description,
                'default_amount' => $this->default_amount,
                'level_id' => $this->level_id ?: null,
            ]);
            $this->success('Kategori biaya berhasil diperbarui.');
        } else {
            FeeCategory::create([
                'name' => $this->name,
                'code' => $this->code,
                'description' => $this->description,
                'default_amount' => $this->default_amount,
                'level_id' => $this->level_id ?: null,
            ]);
            $this->success('Kategori biaya berhasil ditambahkan.');
        }

        $this->categoryModal = false;
        $this->reset(['name', 'code', 'description', 'default_amount', 'level_id', 'editing']);
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->deleteModal = true;
    }

    public function delete(): void
    {
        $category = FeeCategory::find($this->deletingId);
        if (!$category) return;

        if ($category->billings()->exists()) {
            $this->error('Kategori tidak bisa dihapus karena sudah digunakan dalam penagihan.');
            $this->deleteModal = false;
            return;
        }

        $category->delete();
        $this->success('Kategori biaya berhasil dihapus.');
        $this->deleteModal = false;
    }

    public function with(): array
    {
        return [
            'categories' => FeeCategory::with('level')->latest()->paginate(10),
            'levels' => Level::all(),
        ];
    }
}; ?>

<div class="p-6">
    <x-header title="Kategori Biaya" subtitle="Daftar jenis biaya sekolah (SPP, Gedung, dll)." separator>
        <x-slot:actions>
            <x-button label="Tambah Kategori" icon="o-plus" class="btn-primary" wire:click="create" />
        </x-slot:actions>
    </x-header>

    <div class="overflow-x-auto border rounded-xl border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
        <table class="table">
            <thead>
                <tr>
                    <th class="bg-base-200">Kode</th>
                    <th class="bg-base-200">Nama Kategori</th>
                    <th class="bg-base-200 text-center">Jenjang</th>
                    <th class="bg-base-200 text-right">Nominal Default</th>
                    <th class="bg-base-200 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $category)
                    <tr class="hover" wire:key="{{ $category->id }}">
                        <td class="font-mono text-xs opacity-70">
                            {{ $category->code }}
                        </td>
                        <td>
                            <div class="flex flex-col">
                                <span class="font-bold">{{ $category->name }}</span>
                                @if($category->description)
                                    <span class="text-xs opacity-60">{{ $category->description }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="text-center opacity-70">
                            {{ $category->level ? $category->level->name : 'Semua Jenjang' }}
                        </td>
                        <td class="text-right font-mono">
                            Rp {{ number_format($category->default_amount, 0, ',', '.') }}
                        </td>
                        <td class="text-right">
                            <div class="flex justify-end gap-1">
                                <x-button icon="o-pencil-square" wire:click="edit({{ $category->id }})" ghost sm />
                                <x-button icon="o-trash" class="text-error" wire:click="confirmDelete({{ $category->id }})" ghost sm />
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

    {{-- Add/Edit Modal --}}
    <x-modal wire:model="categoryModal" class="backdrop-blur">
        <x-header :title="$editing ? 'Edit Kategori' : 'Tambah Kategori'" subtitle="Deskripsikan kategori biaya baru." separator />

        <div class="grid grid-cols-1 gap-4 text-left">
            <x-input wire:model="code" label="Kode Kategori" placeholder="Contoh: SPP-10" />
            <x-input wire:model="name" label="Nama Kategori" placeholder="Contoh: SPP Kelas 10" />
            
            <x-select wire:model="level_id" label="Jenjang (Opsional)" placeholder="Semua Jenjang" :options="$levels" />

            <x-input wire:model="default_amount" type="number" label="Nominal Default" icon="o-banknotes" />
            <x-textarea wire:model="description" label="Deskripsi" rows="2" />
        </div>

        <x-slot:actions>
            <x-button label="Batal" @click="$set('categoryModal', false)" />
            <x-button label="Simpan" class="btn-primary" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-modal>

    {{-- Delete Confirmation Modal --}}
    <x-modal wire:model="deleteModal" class="backdrop-blur">
        <x-header title="Hapus Kategori Biaya?" subtitle="Apakah Anda yakin ingin menghapus kategori biaya ini? Aksi ini tidak dapat dibatalkan." separator />
        
        <x-slot:actions>
            <x-button label="Batal" @click="$set('deleteModal', false)" />
            <x-button label="Hapus" class="btn-error" wire:click="delete" spinner="delete" />
        </x-slot:actions>
    </x-modal>
</div>
