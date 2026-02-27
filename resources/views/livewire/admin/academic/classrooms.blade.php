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
            'class_level' => ['nullable', 'integer', 'min:1', 'max:13'],
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

<div class="p-6">
    <x-header title="Manajemen Kelas" subtitle="Kelola rombongan belajar dan wali kelas." separator>
        <x-slot:actions>
            <x-select wire:model.live="academic_year_id" :options="$years" placeholder="Semua Tahun" class="w-48" />
            <x-button label="Tambah Kelas" icon="o-plus" class="btn-primary" wire:click="createNew" wire:loading.attr="disabled" />
        </x-slot:actions>
    </x-header>

    <div class="bg-base-100 rounded-lg shadow-sm border border-base-200">
        <table class="table">
            <thead>
                <tr>
                    <th class="bg-base-200">Nama Kelas</th>
                    <th class="bg-base-200">Jenjang</th>
                    <th class="bg-base-200">Tingkat</th>
                    <th class="bg-base-200">Fase</th>
                    <th class="bg-base-200">Tahun Ajaran</th>
                    <th class="bg-base-200">Wali Kelas</th>
                    <th class="bg-base-200 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($classrooms as $classroom)
                    <tr wire:key="{{ $classroom->id }}" class="hover">
                        <td><span class="font-bold">{{ $classroom->name }}</span></td>
                        <td><x-badge :label="$classroom->level->name" class="badge-outline badge-sm" /></td>
                        <td class="opacity-70">{{ $classroom->class_level ? 'Kelas ' . $classroom->class_level : '-' }}</td>
                        <td>
                            @if($classroom->getPhase())
                                <x-badge :label="$classroom->phase_label" class="badge-accent badge-sm" />
                            @else
                                <span class="opacity-30">-</span>
                            @endif
                        </td>
                        <td class="opacity-70">{{ $classroom->academicYear->name }}</td>
                        <td class="opacity-70">{{ $classroom->homeroomTeacher?->name ?? 'Belum Ditentukan' }}</td>
                        <td class="text-right">
                            <div class="flex justify-end gap-1">
                                <x-button icon="o-pencil-square" wire:click="edit({{ $classroom->id }})" ghost sm wire:loading.attr="disabled" />
                                <x-button 
                                    icon="o-trash" 
                                    class="text-error" 
                                    wire:confirm="Yakin ingin menghapus kelas ini?" 
                                    wire:click="delete({{ $classroom->id }})" 
                                    ghost sm 
                                />
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $classrooms->links() }}
    </div>

    <x-modal wire:model="classroomModal" class="backdrop-blur" persistent>
        <x-header :title="$editing ? 'Edit Kelas' : 'Tambah Kelas Baru'" subtitle="Lengkapi detail rombongan belajar di bawah ini." separator />

        <form wire:submit="save">
            <div class="space-y-4">
                <x-input wire:model="name" label="Nama Kelas (Contoh: Kelas 1 A, Paket B Smt 1)" required />

                <div class="grid grid-cols-2 gap-4">
                    <x-select wire:model.live="level_id" label="Jenjang" :options="$levels" placeholder="Pilih Jenjang" required />
                    <x-select wire:model="class_level" label="Tingkat Kelas" :options="$classLevelOptions" placeholder="Pilih Tingkat" />
                </div>

                <x-select wire:model="academic_year_id" label="Tahun Ajaran" :options="$years" required />
                <x-select wire:model="homeroom_teacher_id" label="Wali Kelas (Opsional)" :options="$teachers" placeholder="Pilih Wali Kelas" />
            </div>

            <x-slot:actions>
                <x-button label="Batal" @click="$set('classroomModal', false)" />
                <x-button label="Simpan" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </form>
    </x-modal>
</div>
