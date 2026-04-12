<?php

declare(strict_types=1);

use App\Models\DiniyahSubject;
use App\Models\Level;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public bool $myModal = false;
    
    // Form fields
    public ?int $editingSubjectId = null;
    public string $name = '';
    public ?string $code = null;
    public string $assessment_type = 'numeric';
    public ?string $target = null;
    public bool $has_practice = false;
    public int $kkm = 70;
    public ?int $level_id = null;

    public function mount(): void
    {
        // Permission check
        if (!auth()->user()->isAdmin() && !auth()->user()->isGuru()) {
            abort(403);
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->myModal = true;
    }

    public function edit(int $id): void
    {
        $subject = DiniyahSubject::findOrFail($id);
        $this->editingSubjectId = $id;
        $this->name = $subject->name;
        $this->code = $subject->code;
        $this->assessment_type = $subject->assessment_type;
        $this->target = $subject->target;
        $this->has_practice = $subject->has_practice;
        $this->kkm = $subject->kkm;
        $this->level_id = $subject->level_id;
        $this->myModal = true;
    }

    public function save(): void
    {
        if (!auth()->user()->isAdmin()) {
            $this->dispatch('toast', type: 'error', message: 'Hanya Admin yang dapat memodifikasi mata pelajaran.');
            return;
        }

        $this->validate([
            'name' => 'required|string|max:255',
            'assessment_type' => 'required|in:numeric,target_achievement',
            'level_id' => 'required|exists:levels,id',
        ]);

        DiniyahSubject::updateOrCreate(
            ['id' => $this->editingSubjectId],
            [
                'name' => $this->name,
                'code' => $this->code,
                'assessment_type' => $this->assessment_type,
                'target' => $this->assessment_type === 'target_achievement' ? $this->target : null,
                'has_practice' => $this->assessment_type === 'numeric' ? $this->has_practice : false,
                'kkm' => $this->kkm,
                'level_id' => $this->level_id,
            ]
        );

        $this->myModal = false;
        $this->dispatch('toast', type: 'success', message: 'Mata pelajaran diniyah berhasil disimpan.');
    }

    public function delete(int $id): void
    {
        if (!auth()->user()->isAdmin()) {
            $this->dispatch('toast', type: 'error', message: 'Akses ditolak.');
            return;
        }

        DiniyahSubject::findOrFail($id)->delete();
        $this->dispatch('toast', type: 'success', message: 'Mata pelajaran diniyah berhasil dihapus.');
    }

    private function resetForm(): void
    {
        $this->editingSubjectId = null;
        $this->name = '';
        $this->code = null;
        $this->assessment_type = 'numeric';
        $this->target = null;
        $this->has_practice = false;
        $this->kkm = 70;
        $this->level_id = null;
    }

    public function with(): array
    {
        return [
            'subjects' => DiniyahSubject::with('level')
                ->where('name', 'like', "%{$this->search}%")
                ->orderBy('level_id')
                ->orderBy('name')
                ->paginate(10),
            'levels' => Level::whereIn('education_level', ['sd', 'smp', 'sma'])->get(),
            'isAdmin' => auth()->user()->isAdmin(),
        ];
    }
}; ?>

<div class="p-6">
    <x-ui.header :title="__('Mata Pelajaran Diniyah')" :subtitle="__('Kelola daftar mata pelajaran diniyah dan metode penilaiannya.')" separator>
        @if ($isAdmin)
            <x-slot:actions>
                <x-ui.button :label="__('Tambah Mapel')" icon="o-plus" class="btn-primary" wire:click="create" />
            </x-slot:actions>
        @endif
    </x-ui.header>

    <div class="mb-4 flex justify-between items-center gap-4">
        <div class="flex-1 max-w-sm">
            <x-ui.input wire:model.live.debounce.300ms="search" icon="o-magnifying-glass" :placeholder="__('Cari nama mata pelajaran...')" />
        </div>
    </div>

    <x-ui.card shadow padding="false">
        <x-ui.table 
            :headers="[
                ['key' => 'id', 'label' => '#', 'class' => 'w-10'],
                ['key' => 'name', 'label' => __('Nama Mata Pelajaran')],
                ['key' => 'level.name', 'label' => __('Jenjang')],
                ['key' => 'assessment_type', 'label' => __('Tipe Penilaian')],
                ['key' => 'details', 'label' => __('Detail')],
                ['key' => 'actions', 'label' => '', 'sortable' => false]
            ]" 
            :rows="$subjects" 
            with-pagination
        >
            @scope('cell_assessment_type', $subject)
                <x-ui.badge 
                    :value="$subject->assessment_type === 'numeric' ? 'Angka' : 'Target & Capaian'" 
                    :class="$subject->assessment_type === 'numeric' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'"
                />
            @endscope

            @scope('cell_details', $subject)
                <div class="text-xs text-slate-500">
                    <span class="mr-2">KKM: {{ $subject->kkm }}</span>
                    @if($subject->assessment_type === 'numeric')
                        <span>{{ __('Praktek:') }} {{ $subject->has_practice ? 'Ya' : 'Tidak' }}</span>
                    @else
                        <span class="italic">{{ __('Target:') }} {{ $subject->target ?: '-' }}</span>
                    @endif
                </div>
            @endscope

            @scope('cell_actions', $subject)
                @if(auth()->user()->isAdmin())
                    <div class="flex justify-end gap-2">
                        <x-ui.button icon="o-pencil" class="btn-sm btn-ghost" wire:click="edit({{ $subject->id }})" />
                        <x-ui.button 
                            icon="o-trash" 
                            class="btn-sm btn-ghost text-error" 
                            wire:confirm="Apakah Anda yakin ingin menghapus mata pelajaran ini?"
                            wire:click="delete({{ $subject->id }})" 
                        />
                    </div>
                @endif
            @endscope
        </x-ui.table>
    </x-ui.card>

    {{-- Modal Form --}}
    <x-ui.modal wire:model="myModal" :title="$editingSubjectId ? __('Edit Mata Pelajaran') : __('Tambah Mata Pelajaran')" separator>
        <div class="grid gap-4">
            <x-ui.select wire:model.live="level_id" :label="__('Jenjang / Level')" :options="$levels" placeholder="Pilih Jenjang" />
            <x-ui.input wire:model="name" :label="__('Nama Mata Pelajaran')" placeholder="Contoh: Hafalan Juz Amma" />
            <div class="grid grid-cols-2 gap-4">
                <x-ui.input wire:model="code" :label="__('Kode (Opsional)')" placeholder="Contoh: DIN01" />
                <x-ui.input wire:model="kkm" :label="__('KKM')" type="number" />
            </div>
            
            <x-ui.select 
                wire:model.live="assessment_type" 
                :label="__('Metode Penilaian')" 
                :options="[
                    ['id' => 'numeric', 'name' => 'Angka (Pengetahuan, Praktek, Sikap)'],
                    ['id' => 'target_achievement', 'name' => 'Target & Capaian (Hafalan/Ibadah)']
                ]" 
            />

            @if($assessment_type === 'numeric')
                <x-ui.checkbox wire:model="has_practice" :label="__('Memiliki Aspek Penilaian Praktek?')" />
            @else
                <x-ui.textarea wire:model="target" :label="__('Target Capaian (Sama untuk semua siswa)')" placeholder="Contoh: Hafal Surat An-Naba s/d Al-Inshiqaq" rows="2" />
            @endif
        </div>

        <x-slot:actions>
            <x-ui.button :label="__('Batal')" wire:click="$set('myModal', false)" />
            <x-ui.button :label="__('Simpan')" class="btn-primary" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-ui.modal>
</div>
