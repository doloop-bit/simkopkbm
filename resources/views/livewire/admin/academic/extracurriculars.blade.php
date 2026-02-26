<?php

declare(strict_types=1);

use App\Models\ExtracurricularActivity;
use App\Models\Level;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.admin.layouts.app')] class extends Component {
    use WithPagination;

    public string $name = '';
    public string $instructor = '';
    public string $description = '';
    public ?int $level_id = null;
    public bool $is_active = true;

    // Filters
    public string $search = '';
    public ?int $filterLevelId = null;

    public ?ExtracurricularActivity $editing = null;
    public bool $isModalOpen = false;

    public function createNew(): void
    {
        $this->reset(['name', 'instructor', 'description', 'level_id', 'is_active', 'editing']);
        $this->resetValidation();
        $this->dispatch('open-activity-modal');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterLevelId(): void
    {
        $this->resetPage();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'instructor' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'level_id' => ['required', 'exists:levels,id'],
            'is_active' => ['boolean'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'level_id' => 'Jenjang',
            'name' => 'Nama Ekstrakurikuler',
            'instructor' => 'Pembina',
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editing) {
            $this->editing->update($validated);
            \Flux::toast('Ekstrakurikuler berhasil diperbarui.');
        } else {
            ExtracurricularActivity::create($validated);
            \Flux::toast('Ekstrakurikuler berhasil ditambahkan.');
        }

        $this->reset(['name', 'instructor', 'description', 'level_id', 'is_active', 'editing']);
        $this->dispatch('activity-saved');
    }


    public function edit(ExtracurricularActivity $activity): void
    {
        $this->editing = $activity;
        $this->name = $activity->name;
        $this->instructor = $activity->instructor ?? '';
        $this->description = $activity->description ?? '';
        $this->level_id = $activity->level_id;
        $this->is_active = $activity->is_active;

        $this->dispatch('open-activity-modal');
    }

    public function delete(ExtracurricularActivity $activity): void
    {
        $activity->delete();
        \Flux::toast('Ekstrakurikuler berhasil dihapus.');
    }

    public function toggleStatus(ExtracurricularActivity $activity): void
    {
        $activity->update(['is_active' => !$activity->is_active]);
        \Flux::toast('Status berhasil diubah.');
    }

    public function with(): array
    {
        return [
            'activities' => ExtracurricularActivity::with('level')
                ->when($this->search, function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%')
                          ->orWhere('instructor', 'like', '%' . $this->search . '%');
                })
                ->when($this->filterLevelId, fn($q) => $q->where('level_id', $this->filterLevelId))
                ->latest()
                ->paginate(15),
            'levels' => Level::where('education_level', '!=', 'PAUD')->get(),
        ];
    }
}; ?>

<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Ekstrakurikuler</flux:heading>
            <flux:subheading>Kelola daftar kegiatan ekstrakurikuler untuk siswa.</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="createNew" wire:loading.attr="disabled">Tambah Ekskul</flux:button>
    </div>

    <div class="flex flex-col md:flex-row gap-4 mb-6 items-center justify-between">
        <div class="flex flex-col md:flex-row flex-1 gap-4 w-full">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari nama ekskul atau pembina..." icon="magnifying-glass" class="w-full md:w-80" />
            
            <flux:select wire:model.live="filterLevelId" placeholder="Semua Jenjang" class="w-full md:w-64">
                <option value="">Semua Jenjang</option>
                @foreach($levels as $level)
                    <option value="{{ $level->id }}">{{ $level->name }}</option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <div class="overflow-hidden border rounded-lg border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm text-left border-collapse">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700">Jenjang</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700">Nama Kegiatan</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700">Pembina</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700">Status</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($activities as $activity)
                    <tr wire:key="{{ $activity->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                        <td class="px-4 py-3">
                            <flux:badge size="sm" variant="neutral">{{ $activity->level?->name ?? '-' }}</flux:badge>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-zinc-900 dark:text-white">{{ $activity->name }}</div>
                            @if($activity->description)
                                <div class="text-xs text-zinc-500 line-clamp-1">{{ $activity->description }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                            {{ $activity->instructor ?: '-' }}
                        </td>
                        <td class="px-4 py-3">
                            <button wire:click="toggleStatus({{ $activity->id }})" class="focus:outline-none">
                                @if($activity->is_active)
                                    <flux:badge size="sm" color="green">Aktif</flux:badge>
                                @else
                                    <flux:badge size="sm" color="red">Non-aktif</flux:badge>
                                @endif
                            </button>
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="edit({{ $activity->id }})" wire:loading.attr="disabled" />
                            <flux:button size="sm" variant="ghost" icon="trash" class="text-red-500" wire:confirm="Yakin ingin menghapus ekstrakurikuler ini?" wire:click="delete({{ $activity->id }})" />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-zinc-500">
                            Belum ada data ekstrakurikuler.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $activities->links() }}
    </div>

    {{-- Activity Create/Edit Modal --}}
    <flux:modal name="activity-modal" class="max-w-md" @open-activity-modal.window="$flux.modal('activity-modal').show()" x-on:activity-saved.window="$flux.modal('activity-modal').close()">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editing ? 'Edit Ekstrakurikuler' : 'Tambah Ekstrakurikuler' }}</flux:heading>
                <flux:subheading>Lengkapi detail kegiatan di bawah ini.</flux:subheading>
            </div>

            <flux:select wire:model="level_id" label="Jenjang">
                <option value="">Pilih Jenjang</option>
                @foreach($levels as $level)
                    <option value="{{ $level->id }}">{{ $level->name }}</option>
                @endforeach
            </flux:select>

            <flux:input wire:model="name" label="Nama Kegiatan" required placeholder="e.g. Futsal, Pramuka" />
            
            <flux:input wire:model="instructor" label="Pembina / Pelatih" placeholder="e.g. Pak Budi Santoso" />

            <flux:textarea wire:model="description" label="Keterangan (Opsional)" rows="3" placeholder="Deskripsi singkat kegiatan..." />

            <div class="flex items-center gap-3">
                <flux:checkbox wire:model="is_active" label="Kegiatan ini aktif" />
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
