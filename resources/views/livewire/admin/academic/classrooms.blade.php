<?php

declare(strict_types=1);

use App\Models\Classroom;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.admin.layouts.app')] class extends Component {
    use WithPagination;

    public string $name = '';
    public ?int $academic_year_id = null;
    public ?int $level_id = null;
    public ?int $class_level = null;
    public ?int $homeroom_teacher_id = null;
    public bool $classroomModal = false;

    public ?Classroom $editing = null;
    public array $classLevelOptions = [];

    public function createNew(): void
    {
        $this->reset(['name', 'class_level', 'homeroom_teacher_id', 'editing', 'classLevelOptions']);
        $this->resetValidation();
        $this->classroomModal = true;
    }

    public function mount(): void
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if ($activeYear) {
            $this->academic_year_id = $activeYear->id;
        }
    }

    public function updatedLevelId(): void
    {
        $this->loadClassLevelOptions();
        $this->class_level = null;
    }

    public function loadClassLevelOptions(): void
    {
        if (!$this->level_id) {
            $this->classLevelOptions = [];
            return;
        }

        $level = Level::find($this->level_id);
        if (!$level || !$level->phase_map) {
            $this->classLevelOptions = [];
            return;
        }

        // Build options from phase_map keys
        // e.g. {"1": "A", "2": "A", "3": "B"} → [1 => "Kelas 1 (Fase A)", 2 => "Kelas 2 (Fase A)", 3 => "Kelas 3 (Fase B)"]
        $options = [];
        foreach ($level->phase_map as $classLevel => $phase) {
            $options[] = [
                'value' => (int) $classLevel,
                'label' => "Kelas {$classLevel} (Fase {$phase})",
            ];
        }
        $this->classLevelOptions = $options;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'level_id' => ['required', 'exists:levels,id'],
            'class_level' => ['nullable', 'integer', 'min:0', 'max:13'],
            'homeroom_teacher_id' => ['nullable', 'exists:users,id'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editing) {
            $this->editing->update($validated);
        } else {
            Classroom::create($validated);
        }

        $this->reset(['name', 'class_level', 'homeroom_teacher_id', 'editing', 'classLevelOptions']);
        $this->classroomModal = false;
    }

    public function edit(Classroom $classroom): void
    {
        $this->editing = $classroom;
        $this->name = $classroom->name;
        $this->academic_year_id = $classroom->academic_year_id;
        $this->level_id = $classroom->level_id;
        $this->class_level = $classroom->class_level;
        $this->homeroom_teacher_id = $classroom->homeroom_teacher_id;

        $this->loadClassLevelOptions();
        $this->classroomModal = true;
    }

    public function delete(Classroom $classroom): void
    {
        $classroom->delete();
    }

    public function with(): array
    {
        return [
            'classrooms' => Classroom::with(['academicYear', 'level', 'homeroomTeacher'])
                ->when($this->academic_year_id, fn($q) => $q->where('academic_year_id', $this->academic_year_id))
                ->latest()
                ->paginate(15),
            'years' => AcademicYear::all(),
            'levels' => Level::all(),
            'teachers' => User::where('role', 'guru')->get(),
        ];
    }
}; ?>

<div class="p-6 space-y-6 text-slate-900 dark:text-white">
    <x-ui.header :title="__('Manajemen Kelas')" :subtitle="__('Kelola rombongan belajar dan wali kelas.')" separator>
        <x-slot:actions>
            <div class="flex items-center gap-3">
                <x-ui.select wire:model.live="academic_year_id" :options="$years" :placeholder="__('Semua Tahun')" class="w-48" />
                <x-ui.button :label="__('Tambah Kelas')" icon="o-plus" class="btn-primary" wire:click="createNew" wire:loading.attr="disabled" />
            </div>
        </x-slot:actions>
    </x-ui.header>

    <x-ui.card shadow padding="false">
        <x-ui.table 
            :headers="[
                ['key' => 'name', 'label' => __('Nama Kelas')],
                ['key' => 'level', 'label' => __('Jenjang')],
                ['key' => 'class_level', 'label' => __('Tingkat')],
                ['key' => 'phase', 'label' => __('Fase')],
                ['key' => 'academic_year', 'label' => __('Tahun Ajaran')],
                ['key' => 'homeroom', 'label' => __('Wali Kelas')],
                ['key' => 'actions', 'label' => '', 'class' => 'text-right']
            ]" 
            :rows="$classrooms"
        >
            @scope('cell_name', $classroom)
                <span class="font-bold text-slate-900 dark:text-white">{{ $classroom->name }}</span>
            @endscope

            @scope('cell_level', $classroom)
                <x-ui.badge :label="$classroom->level->name" class="bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 text-[10px]" />
            @endscope

            @scope('cell_class_level', $classroom)
                <span class="text-slate-500 dark:text-slate-400 text-sm">
                    {{ $classroom->class_level !== null ? __('Kelas :level', ['level' => $classroom->class_level]) : '-' }}
                </span>
            @endscope

            @scope('cell_phase', $classroom)
                @if($classroom->getPhase())
                    <x-ui.badge :label="$classroom->phase_label" class="bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 text-[10px] font-black" />
                @else
                    <span class="opacity-30">-</span>
                @endif
            @endscope

            @scope('cell_academic_year', $classroom)
                <span class="text-slate-500 dark:text-slate-400 text-sm">{{ $classroom->academicYear->name }}</span>
            @endscope

            @scope('cell_homeroom', $classroom)
                <div class="flex items-center gap-2">
                    <x-ui.icon name="o-user" class="size-3.5 opacity-40" />
                    <span class="text-slate-600 dark:text-slate-400 text-sm">
                        {{ $classroom->homeroomTeacher?->name ?? __('Belum Ditentukan') }}
                    </span>
                </div>
            @endscope

            @scope('cell_actions', $classroom)
                <div class="flex justify-end gap-1">
                    <x-ui.button icon="o-pencil-square" wire:click="edit({{ $classroom->id }})" ghost wire:loading.attr="disabled" />
                    <x-ui.button 
                        icon="o-trash" 
                        class="text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20" 
                        wire:confirm="{{ __('Yakin ingin menghapus kelas ini?') }}" 
                        wire:click="delete({{ $classroom->id }})" 
                        ghost 
                    />
                </div>
            @endscope
        </x-ui.table>
    </x-ui.card>

    <div class="mt-4">
        {{ $classrooms->links() }}
    </div>

    <x-ui.modal wire:model="classroomModal" persistent>
        <x-ui.header :title="$editing ? __('Edit Kelas') : __('Tambah Kelas Baru')" :subtitle="__('Lengkapi detail rombongan belajar di bawah ini.')" separator />

        <form wire:submit="save" class="space-y-6">
            <x-ui.input wire:model="name" :label="__('Nama Kelas (Contoh: Kelas 1 A, Paket B Smt 1)')" required />

            <div class="grid grid-cols-2 gap-4">
                <x-ui.select wire:model.live="level_id" :label="__('Jenjang')" :options="$levels" :placeholder="__('Pilih Jenjang')" required />
                <x-ui.select wire:model="class_level" :label="__('Tingkat Kelas')" :options="$classLevelOptions" :placeholder="__('Pilih Tingkat')" option-label="label" option-value="value" />
            </div>

            <x-ui.select wire:model="academic_year_id" :label="__('Tahun Ajaran')" :options="$years" required />
            <x-ui.select wire:model="homeroom_teacher_id" :label="__('Wali Kelas (Opsional)')" :options="$teachers" :placeholder="__('Pilih Wali Kelas')" />

            <div class="flex justify-end gap-2 pt-4">
                <x-ui.button :label="__('Batal')" ghost @click="show = false" />
                <x-ui.button :label="__('Simpan')" type="submit" class="btn-primary" spinner="save" />
            </div>
        </form>
    </x-ui.modal>
</div>
