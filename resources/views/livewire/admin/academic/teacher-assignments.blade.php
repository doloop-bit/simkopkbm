<?php

declare(strict_types=1);

use App\Models\TeacherAssignment;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.admin.layouts.app')] class extends Component {
    public ?int $academic_year_id = null;
    public ?int $classroom_id = null;
    public ?int $teacher_id = null;
    public ?int $subject_id = null;
    public string $type = 'subject_teacher';
    public bool $assignmentModal = false;

    public ?TeacherAssignment $editing = null;

    public function mount(): void
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if ($activeYear) {
            $this->academic_year_id = $activeYear->id;
        }
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'teacher_id' => ['required', 'exists:users,id'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
            'type' => ['required', 'in:class_teacher,subject_teacher,homeroom'],
        ];
    }

    public function createNew(): void
    {
        $this->reset(['teacher_id', 'subject_id', 'type', 'editing']);
        $this->assignmentModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'academic_year_id' => $this->academic_year_id,
            'classroom_id' => $this->classroom_id,
            'teacher_id' => $this->teacher_id,
            'subject_id' => $this->type === 'subject_teacher' ? $this->subject_id : null,
            'type' => $this->type,
        ];

        if ($this->editing) {
            $this->editing->update($data);
        } else {
            TeacherAssignment::create($data);
        }

        $this->reset(['teacher_id', 'subject_id', 'type', 'editing']);
        $this->assignmentModal = false;
        session()->flash('success', 'Penugasan guru berhasil disimpan.');
    }

    public function edit(TeacherAssignment $assignment): void
    {
        $this->editing = $assignment;
        $this->teacher_id = $assignment->teacher_id;
        $this->subject_id = $assignment->subject_id;
        $this->type = $assignment->type;
        $this->classroom_id = $assignment->classroom_id;

        $this->assignmentModal = true;
    }

    public function delete(TeacherAssignment $assignment): void
    {
        $assignment->delete();
        session()->flash('success', 'Penugasan guru berhasil dihapus.');
    }

    public function with(): array
    {
        return [
            'assignments' => TeacherAssignment::with(['teacher', 'subject', 'classroom.academicYear'])
                ->when($this->academic_year_id, fn($q) => $q->where('academic_year_id', $this->academic_year_id))
                ->when($this->classroom_id, fn($q) => $q->where('classroom_id', $this->classroom_id))
                ->get(),
            'years' => AcademicYear::all(),
            'classrooms' => Classroom::when($this->academic_year_id, fn($q) => $q->where('academic_year_id', $this->academic_year_id))->get(),
            'subjects' => Subject::all(),
            'teachers' => User::where('role', 'guru')->get(),
        ];
    }
}; ?>

<div class="p-6">
    <x-header title="Penugasan Guru" subtitle="Atur penugasan guru untuk mata pelajaran dan kelas." separator>
        <x-slot:actions>
            <x-select wire:model.live="academic_year_id" :options="$years" placeholder="Semua Tahun" class="w-48" />
            <x-select wire:model.live="classroom_id" :options="$classrooms" placeholder="Semua Kelas" class="w-48" />
            <x-button label="Tambah Penugasan" icon="o-plus" class="btn-primary" wire:click="createNew" />
        </x-slot:actions>
    </x-header>

    @if (session('success'))
        <x-alert title="Berhasil" icon="o-check-circle" class="alert-success mb-6" dismissible>
            {{ session('success') }}
        </x-alert>
    @endif

    <div class="bg-base-100 rounded-xl shadow-sm border border-base-200 overflow-hidden">
        <table class="table">
            <thead>
                <tr>
                    <th class="bg-base-200">Guru</th>
                    <th class="bg-base-200">Kelas</th>
                    <th class="bg-base-200">Mata Pelajaran</th>
                    <th class="bg-base-200">Tipe</th>
                    <th class="bg-base-200 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($assignments as $assignment)
                    <tr wire:key="{{ $assignment->id }}" class="hover">
                        <td class="font-bold">
                            {{ $assignment->teacher->name }}
                        </td>
                        <td class="opacity-70 text-sm">
                            {{ $assignment->classroom->name }} ({{ $assignment->classroom->academicYear->name }})
                        </td>
                        <td class="opacity-70 text-sm">
                            {{ $assignment->subject?->name ?? '-' }}
                        </td>
                        <td>
                            <x-badge 
                                :label="match($assignment->type) {
                                    'class_teacher' => 'Guru Kelas',
                                    'subject_teacher' => 'Guru Mapel',
                                    'homeroom' => 'Wali Kelas',
                                    default => $assignment->type
                                }" 
                                class="badge-outline badge-sm" 
                            />
                        </td>
                        <td class="text-right">
                            <div class="flex justify-end gap-1">
                                <x-button icon="o-pencil-square" wire:click="edit({{ $assignment->id }})" ghost sm />
                                <x-button 
                                    icon="o-trash" 
                                    class="text-error" 
                                    wire:confirm="Yakin ingin menghapus penugasan ini?" 
                                    wire:click="delete({{ $assignment->id }})" 
                                    ghost sm 
                                />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-12 opacity-40 italic">
                            Belum ada penugasan guru yang ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-modal wire:model="assignmentModal" class="backdrop-blur">
        <x-header :title="$editing ? 'Edit Penugasan' : 'Tambah Penugasan Baru'" subtitle="Lengkapi detail penugasan guru di bawah ini." separator />

        <form wire:submit="save">
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <x-select wire:model="academic_year_id" label="Tahun Ajaran" :options="$years" required />
                    <x-select wire:model.live="classroom_id" label="Kelas" :options="Classroom::where('academic_year_id', $academic_year_id)->get()" placeholder="Pilih Kelas" required />
                </div>

                <x-select wire:model="teacher_id" label="Guru" :options="$teachers" placeholder="Pilih Guru" required />

                <div class="space-y-2">
                    <div class="text-sm font-medium">Tipe Penugasan</div>
                    <x-radio 
                        wire:model.live="type" 
                        :options="[
                            ['id' => 'subject_teacher', 'label' => 'Guru Mata Pelajaran'],
                            ['id' => 'class_teacher', 'label' => 'Guru Kelas'],
                            ['id' => 'homeroom', 'label' => 'Wali Kelas'],
                        ]"
                    />
                </div>

                @if($type === 'subject_teacher')
                    <x-select wire:model="subject_id" label="Mata Pelajaran" :options="$subjects" placeholder="Pilih Mata Pelajaran" required />
                @endif
            </div>

            <x-slot:actions>
                <x-button label="Batal" @click="$set('assignmentModal', false)" />
                <x-button label="Simpan" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </form>
    </x-modal>
</div>
