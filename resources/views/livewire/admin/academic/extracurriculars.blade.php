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
    public bool $activityModal = false;

    public function createNew(): void
    {
        $this->reset(['name', 'instructor', 'description', 'level_id', 'is_active', 'editing']);
        $this->resetValidation();
        $this->activityModal = true;
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
            session()->flash('success', 'Ekstrakurikuler berhasil diperbarui.');
        } else {
            ExtracurricularActivity::create($validated);
            session()->flash('success', 'Ekstrakurikuler berhasil ditambahkan.');
        }

        $this->reset(['name', 'instructor', 'description', 'level_id', 'is_active', 'editing']);
        $this->activityModal = false;
    }


    public function edit(ExtracurricularActivity $activity): void
    {
        $this->editing = $activity;
        $this->name = $activity->name;
        $this->instructor = $activity->instructor ?? '';
        $this->description = $activity->description ?? '';
        $this->level_id = $activity->level_id;
        $this->is_active = $activity->is_active;

        $this->activityModal = true;
    }

    public function delete(ExtracurricularActivity $activity): void
    {
        $activity->delete();
        session()->flash('success', 'Ekstrakurikuler berhasil dihapus.');
    }

    public function toggleStatus(ExtracurricularActivity $activity): void
    {
        $activity->update(['is_active' => !$activity->is_active]);
        session()->flash('success', 'Status berhasil diubah.');
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
    <x-header title="Ekstrakurikuler" subtitle="Kelola daftar kegiatan ekstrakurikuler untuk siswa." separator>
        <x-slot:actions>
             <x-button label="Tambah Ekskul" icon="o-plus" class="btn-primary" wire:click="createNew" wire:loading.attr="disabled" />
        </x-slot:actions>
    </x-header>

    <div class="flex flex-col md:flex-row gap-4 mb-6 items-center justify-between">
        <div class="flex flex-col md:flex-row flex-1 gap-4 w-full">
            <x-input wire:model.live.debounce.300ms="search" placeholder="Cari nama ekskul atau pembina..." icon="o-magnifying-glass" class="w-full md:w-80" />
            
            <x-select wire:model.live="filterLevelId" placeholder="Semua Jenjang" class="w-full md:w-64" :options="$levels" />
        </div>
    </div>

    @if (session('success'))
        <x-alert title="Berhasil" icon="o-check-circle" class="alert-success mb-6" dismissible>
            {{ session('success') }}
        </x-alert>
    @endif

    <div class="bg-base-100 rounded-xl shadow-sm border border-base-200 overflow-hidden">
        <table class="table">
            <thead>
                <tr>
                    <th class="bg-base-200">Jenjang</th>
                    <th class="bg-base-200">Nama Kegiatan</th>
                    <th class="bg-base-200">Pembina</th>
                    <th class="bg-base-200">Status</th>
                    <th class="bg-base-200 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($activities as $activity)
                    <tr wire:key="{{ $activity->id }}" class="hover">
                        <td>
                            <x-badge :label="$activity->level?->name ?? '-'" class="badge-neutral badge-sm" />
                        </td>
                        <td>
                            <div class="font-bold">{{ $activity->name }}</div>
                            @if($activity->description)
                                <div class="text-xs opacity-60 line-clamp-1">{{ $activity->description }}</div>
                            @endif
                        </td>
                        <td class="opacity-70 text-sm">
                            {{ $activity->instructor ?: '-' }}
                        </td>
                        <td>
                            <button wire:click="toggleStatus({{ $activity->id }})" class="focus:outline-none">
                                @if($activity->is_active)
                                    <x-badge label="Aktif" class="badge-success badge-sm" />
                                @else
                                    <x-badge label="Non-aktif" class="badge-error badge-sm" />
                                @endif
                            </button>
                        </td>
                        <td class="text-right">
                            <div class="flex justify-end gap-1">
                                <x-button icon="o-pencil-square" wire:click="edit({{ $activity->id }})" ghost sm wire:loading.attr="disabled" />
                                <x-button 
                                    icon="o-trash" 
                                    class="text-error" 
                                    wire:confirm="Yakin ingin menghapus ekstrakurikuler ini?" 
                                    wire:click="delete({{ $activity->id }})" 
                                    ghost sm 
                                />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-12 opacity-40">
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
    <x-modal wire:model="activityModal" class="backdrop-blur">
        <x-header :title="$editing ? 'Edit Ekstrakurikuler' : 'Tambah Ekstrakurikuler'" subtitle="Lengkapi detail kegiatan di bawah ini." separator />

        <form wire:submit="save">
            <div class="space-y-4">
                <x-select wire:model="level_id" label="Jenjang" :options="$levels" placeholder="Pilih Jenjang" required />

                <x-input wire:model="name" label="Nama Kegiatan" required placeholder="e.g. Futsal, Pramuka" />
                
                <x-input wire:model="instructor" label="Pembina / Pelatih" placeholder="e.g. Pak Budi Santoso" />

                <x-textarea wire:model="description" label="Keterangan (Opsional)" rows="3" placeholder="Deskripsi singkat kegiatan..." />

                <x-checkbox wire:model="is_active" label="Kegiatan ini aktif" />
            </div>

            <x-slot:actions>
                <x-button label="Batal" @click="$set('activityModal', false)" />
                <x-button label="Simpan" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </form>
    </x-modal>
</div>
