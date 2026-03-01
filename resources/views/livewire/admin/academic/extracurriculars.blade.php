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

<div class="p-6 space-y-6 text-slate-900 dark:text-white">
    <x-ui.header :title="__('Ekstrakurikuler')" :subtitle="__('Kelola daftar kegiatan ekstrakurikuler untuk siswa.')" separator>
        <x-slot:actions>
             <x-ui.button :label="__('Tambah Ekskul')" icon="o-plus" class="btn-primary" wire:click="createNew" wire:loading.attr="disabled" />
        </x-slot:actions>
    </x-ui.header>

    <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
        <div class="flex flex-col md:flex-row flex-1 gap-4 w-full">
            <x-ui.input wire:model.live.debounce.300ms="search" :placeholder="__('Cari nama ekskul atau pembina...')" icon="o-magnifying-glass" class="w-full md:w-80" />
            
            <x-ui.select wire:model.live="filterLevelId" :placeholder="__('Semua Jenjang')" class="w-full md:w-64" :options="$levels" sm />
        </div>
    </div>

    @if (session('success'))
        <x-ui.alert :title="__('Berhasil')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100" dismissible>
            {{ session('success') }}
        </x-ui.alert>
    @endif

    <x-ui.card shadow padding="false">
        <x-ui.table 
            :headers="[
                ['key' => 'level', 'label' => __('Jenjang')],
                ['key' => 'name', 'label' => __('Nama Kegiatan')],
                ['key' => 'instructor', 'label' => __('Pembina')],
                ['key' => 'is_active', 'label' => __('Status')],
                ['key' => 'actions', 'label' => '', 'class' => 'text-right']
            ]" 
            :rows="$activities"
        >
            @scope('cell_level', $activity)
                <x-ui.badge :label="$activity->level?->name ?? '-'" class="bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 text-[10px]" />
            @endscope

            @scope('cell_name', $activity)
                <div class="flex flex-col max-w-xs">
                    <span class="font-bold text-slate-900 dark:text-white">{{ $activity->name }}</span>
                    @if($activity->description)
                        <span class="text-[10px] text-slate-400 line-clamp-1 italic">{{ $activity->description }}</span>
                    @endif
                </div>
            @endscope

            @scope('cell_instructor', $activity)
                <span class="text-sm text-slate-600 dark:text-slate-400">
                    {{ $activity->instructor ?: '-' }}
                </span>
            @endscope

            @scope('cell_is_active', $activity)
                <button wire:click="toggleStatus({{ $activity->id }})" class="focus:outline-none group">
                    @if($activity->is_active)
                        <x-ui.badge :label="__('Aktif')" class="bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 text-[10px] font-black group-hover:brightness-110" />
                    @else
                        <x-ui.badge :label="__('Non-aktif')" class="bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400 text-[10px] font-black group-hover:brightness-110" />
                    @endif
                </button>
            @endscope

            @scope('cell_actions', $activity)
                <div class="flex justify-end gap-1">
                    <x-ui.button icon="o-pencil-square" wire:click="edit({{ $activity->id }})" ghost sm wire:loading.attr="disabled" />
                    <x-ui.button 
                        icon="o-trash" 
                        class="text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/10" 
                        wire:confirm="{{ __('Yakin ingin menghapus ekstrakurikuler ini?') }}" 
                        wire:click="delete({{ $activity->id }})" 
                        ghost sm 
                    />
                </div>
            @endscope
        </x-ui.table>

        @if(collect($activities)->isEmpty())
            <div class="py-12 text-center text-slate-400 italic text-sm">
                {{ __('Belum ada data ekstrakurikuler.') }}
            </div>
        @endif
    </x-ui.card>

    <div class="mt-4">
        {{ $activities->links() }}
    </div>

    {{-- Activity Create/Edit Modal --}}
    <x-ui.modal wire:model="activityModal" persistent>
        <x-ui.header :title="$editing ? __('Edit Ekstrakurikuler') : __('Tambah Ekstrakurikuler')" :subtitle="__('Lengkapi detail kegiatan di bawah ini.')" separator />

        <form wire:submit="save" class="space-y-6">
            <x-ui.select wire:model="level_id" :label="__('Jenjang')" :options="$levels" :placeholder="__('Pilih Jenjang')" required />

            <x-ui.input wire:model="name" :label="__('Nama Kegiatan')" required :placeholder="__('e.g. Futsal, Pramuka')" />
            
            <x-ui.input wire:model="instructor" :label="__('Pembina / Pelatih')" :placeholder="__('e.g. Pak Budi Santoso')" />

            <x-ui.textarea wire:model="description" :label="__('Keterangan (Opsional)')" rows="3" :placeholder="__('Deskripsi singkat kegiatan...')" />

            <x-ui.checkbox wire:model="is_active" :label="__('Kegiatan ini aktif')" />

            <div class="flex justify-end gap-2 pt-4">
                <x-ui.button :label="__('Batal')" ghost @click="show = false" />
                <x-ui.button :label="__('Simpan')" type="submit" class="btn-primary" spinner="save" />
            </div>
        </form>
    </x-ui.modal>
</div>
