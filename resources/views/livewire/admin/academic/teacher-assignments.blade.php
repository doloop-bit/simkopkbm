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

<div class="p-6 space-y-6 text-slate-900 dark:text-white">
    <x-ui.header :title="__('Penugasan Guru')" :subtitle="__('Atur penugasan guru untuk mata pelajaran dan kelas.')" separator>
        <x-slot:actions>
            <div class="flex items-center gap-3">
                <x-ui.select wire:model.live="academic_year_id" :options="$years" :placeholder="__('Tahun Ajaran')" class="w-40" />
                <x-ui.select wire:model.live="classroom_id" :options="$classrooms" :placeholder="__('Semua Kelas')" class="w-48" />
                <x-ui.button :label="__('Tambah Penugasan')" icon="o-plus" class="btn-primary" wire:click="createNew" />
            </div>
        </x-slot:actions>
    </x-ui.header>

    @if (session('success'))
        <x-ui.alert :title="__('Berhasil')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100 mb-6" dismissible>
            {{ session('success') }}
        </x-ui.alert>
    @endif

    <x-ui.card shadow padding="false">
        <x-ui.table 
            :headers="[
                ['key' => 'teacher', 'label' => __('Guru')],
                ['key' => 'classroom', 'label' => __('Kelas')],
                ['key' => 'subject', 'label' => __('Mata Pelajaran')],
                ['key' => 'type', 'label' => __('Tipe')],
                ['key' => 'actions', 'label' => '', 'class' => 'text-right']
            ]" 
            :rows="$assignments"
        >
            @scope('cell_teacher', $assignment)
                <div class="flex flex-col">
                    <span class="font-bold text-slate-900 dark:text-white">{{ $assignment->teacher->name }}</span>
                    <span class="text-[10px] text-slate-400 font-mono tracking-tighter">{{ $assignment->teacher->email }}</span>
                </div>
            @endscope

            @scope('cell_classroom', $assignment)
                <div class="flex flex-col">
                    <span class="text-sm font-medium">{{ $assignment->classroom->name }}</span>
                    <span class="text-[10px] text-slate-400">{{ $assignment->classroom->academicYear->name }}</span>
                </div>
            @endscope

            @scope('cell_subject', $assignment)
                <span class="text-sm text-slate-600 dark:text-slate-400">
                    {{ $assignment->subject?->name ?? '-' }}
                </span>
            @endscope

            @scope('cell_type', $assignment)
                <x-ui.badge 
                    :label="match($assignment->type) {
                        'class_teacher' => __('Guru Kelas'),
                        'subject_teacher' => __('Guru Mapel'),
                        'homeroom' => __('Wali Kelas'),
                        default => $assignment->type
                    }" 
                    class="{{ match($assignment->type) {
                        'class_teacher' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400',
                        'subject_teacher' => 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400',
                        'homeroom' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-400',
                        default => 'bg-slate-100'
                    } }} text-[10px] font-bold" 
                />
            @endscope

            @scope('cell_actions', $assignment)
                <div class="flex justify-end gap-1">
                    <x-ui.button icon="o-pencil-square" wire:click="edit({{ $assignment->id }})" ghost />
                    <x-ui.button 
                        icon="o-trash" 
                        class="text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/10" 
                        wire:confirm="{{ __('Yakin ingin menghapus penugasan ini?') }}" 
                        wire:click="delete({{ $assignment->id }})" 
                        ghost 
                    />
                </div>
            @endscope
        </x-ui.table>

        @if(collect($assignments)->isEmpty())
            <div class="py-12 text-center text-slate-400 italic text-sm">
                {{ __('Belum ada penugasan guru yang ditemukan.') }}
            </div>
        @endif
    </x-ui.card>

    <x-ui.modal wire:model="assignmentModal" persistent>
        <x-ui.header :title="$editing ? __('Edit Penugasan') : __('Tambah Penugasan Baru')" :subtitle="__('Lengkapi detail penugasan guru di bawah ini.')" separator />

        <form wire:submit="save" class="space-y-6">
            <div class="grid grid-cols-2 gap-4">
                <x-ui.select wire:model="academic_year_id" :label="__('Tahun Ajaran')" :options="$years" required />
                <x-ui.select wire:model.live="classroom_id" :label="__('Kelas')" :options="App\Models\Classroom::where('academic_year_id', $academic_year_id)->get()" :placeholder="__('Pilih Kelas')" required />
            </div>

            <x-ui.select wire:model="teacher_id" :label="__('Guru')" :options="$teachers" :placeholder="__('Pilih Guru')" required />

            <div class="space-y-3">
                <div class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Tipe Penugasan') }}</div>
                <x-ui.radio 
                    wire:model.live="type" 
                    :options="[
                        ['id' => 'subject_teacher', 'label' => __('Guru Mata Pelajaran')],
                        ['id' => 'class_teacher', 'label' => __('Guru Kelas')],
                        ['id' => 'homeroom', 'label' => __('Wali Kelas')],
                    ]"
                />
            </div>

            @if($type === 'subject_teacher')
                <x-ui.select wire:model="subject_id" :label="__('Mata Pelajaran')" :options="$subjects" :placeholder="__('Pilih Mata Pelajaran')" required />
            @endif

            <div class="flex justify-end gap-2 pt-4">
                <x-ui.button :label="__('Batal')" ghost @click="show = false" />
                <x-ui.button :label="__('Simpan')" type="submit" class="btn-primary" spinner="save" />
            </div>
        </form>
    </x-ui.modal>
</div>
