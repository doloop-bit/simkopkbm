<?php

declare(strict_types=1);

use App\Models\Classroom;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public string $name = '';
    public ?int $academic_year_id = null;
    public ?int $level_id = null;
    public ?int $class_level = null;
    public ?int $homeroom_teacher_id = null;
    public bool $classroomModal = false;

    // Copy Classrooms Modal
    public bool $copyModal = false;
    public ?int $copy_source_year_id = null;
    public ?int $copy_target_year_id = null;
    public bool $copy_homeroom = true;
    public bool $copy_assignments = true;

    // Filter and Sort
    public string $search = '';
    public ?int $filter_level_id = null;
    public string $sortField = 'class_level';
    public string $sortDirection = 'asc';

    public ?Classroom $editing = null;
    public ?Classroom $viewingClassroom = null;
    public bool $detailModal = false;
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

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterLevelId(): void
    {
        $this->resetPage();
    }

    public function updatedAcademicYearId(): void
    {
        $this->resetPage();
    }

    public function toggleSort(): void
    {
        $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
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

    public function viewDetail(int $classroomId): void
    {
        $this->viewingClassroom = Classroom::with([
            'academicYear',
            'level',
            'homeroomTeacher',
            'teacherAssignments.teacher',
            'teacherAssignments.subject',
            'teacherAssignments.diniyahSubject',
            'students.profile.user',
        ])->findOrFail($classroomId);

        $this->detailModal = true;
    }

    public function openCopyModal(): void
    {
        $this->resetValidation();
        $activeYear = AcademicYear::where('is_active', true)->first();
        $this->copy_target_year_id = $this->academic_year_id ?? $activeYear?->id;

        $previousYear = AcademicYear::where('id', '!=', $this->copy_target_year_id)
            ->orderByDesc('end_date')
            ->first();

        $this->copy_source_year_id = $previousYear?->id;
        $this->copy_homeroom = true;
        $this->copy_assignments = true;
        $this->copyModal = true;
    }

    public function copyClassrooms(): void
    {
        $this->validate([
            'copy_source_year_id' => ['required', 'exists:academic_years,id', 'different:copy_target_year_id'],
            'copy_target_year_id' => ['required', 'exists:academic_years,id'],
        ], [
            'copy_source_year_id.different' => __('Tahun ajaran asal dan tujuan tidak boleh sama.'),
        ]);

        $copiedCount = Classroom::copyFromYear(
            (int) $this->copy_source_year_id,
            (int) $this->copy_target_year_id,
            $this->copy_homeroom,
            $this->copy_assignments
        );

        $this->academic_year_id = (int) $this->copy_target_year_id;
        $this->copyModal = false;

        session()->flash('success', __('Berhasil menyalin :count kelas ke tahun ajaran tujuan.', [
            'count' => $copiedCount,
        ]));
    }

    public function with(): array
    {
        return [
            'classrooms' => Classroom::with(['academicYear', 'level', 'homeroomTeacher'])
                ->withCount(['students', 'teacherAssignments'])
                ->when($this->academic_year_id, fn($q) => $q->where('academic_year_id', $this->academic_year_id))
                ->when($this->filter_level_id, fn($q) => $q->where('level_id', $this->filter_level_id))
                ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->orderBy($this->sortField, $this->sortDirection)
                ->orderBy('level_id')
                ->orderBy('name')
                ->paginate(15),
            'years' => AcademicYear::all(),
            'levels' => Level::all(),
            'teachers' => User::where('role', 'guru')->get(),
        ];
    }
}; ?>

<div class="p-6 space-y-6 text-slate-900 dark:text-white">
    @if (session('success'))
        <x-ui.alert :title="__('Berhasil')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100" dismissible>
            {{ session('success') }}
        </x-ui.alert>
    @endif

    <x-ui.header :title="__('Manajemen Kelas')" :subtitle="__('Kelola rombongan belajar dan wali kelas.')" separator>
        <x-slot:actions>
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.input wire:model.live.debounce.300ms="search" :placeholder="__('Cari kelas...')" icon="o-magnifying-glass" class="w-64" clearable />
                <x-ui.button :label="__('Salin Kelas')" icon="o-document-duplicate" ghost wire:click="openCopyModal" wire:loading.attr="disabled" />
                <x-ui.button :label="__('Tambah Kelas')" icon="o-plus" class="btn-primary" wire:click="createNew" wire:loading.attr="disabled" />
            </div>
        </x-slot:actions>
    </x-ui.header>

    <div class="flex flex-col md:flex-row gap-4 mb-6 items-center justify-between bg-slate-50/50 dark:bg-slate-900/50 p-4 rounded-2xl border border-slate-100 dark:border-slate-800">
        <div class="flex flex-1 gap-3 w-full md:w-auto">
            <div class="w-full md:w-48">
                <x-ui.select wire:model.live="academic_year_id" :options="$years" :placeholder="__('Semua Tahun')" />
            </div>
            <div class="w-full md:w-48">
                <x-ui.select wire:model.live="filter_level_id" :options="$levels" :placeholder="__('Semua Jenjang')" />
            </div>
        </div>

        <div class="flex gap-2 w-full md:w-auto justify-end">
            <div class="w-full md:w-48">
                <x-ui.select wire:model.live="sortField" :options="[
                    ['id' => 'name', 'name' => __('Nama Kelas')],
                    ['id' => 'level_id', 'name' => __('Jenjang')],
                    ['id' => 'class_level', 'name' => __('Tingkat')],
                    ['id' => 'created_at', 'name' => __('Tanggal Dibuat')],
                ]" />
            </div>
            <x-ui.button :icon="$sortDirection === 'asc' ? 'o-chevron-up' : 'o-chevron-down'" wire:click="toggleSort" ghost />
        </div>
    </div>

    <x-ui.card shadow padding="false">
        <x-ui.table 
            :headers="[
                ['key' => 'name', 'label' => __('Nama Kelas')],
                ['key' => 'level', 'label' => __('Jenjang')],
                ['key' => 'class_level', 'label' => __('Tingkat')],
                ['key' => 'phase', 'label' => __('Fase')],
                ['key' => 'academic_year', 'label' => __('Tahun Ajaran')],
                ['key' => 'homeroom', 'label' => __('Wali Kelas')],
                ['key' => 'students_count', 'label' => __('Siswa'), 'class' => 'text-center'],
                ['key' => 'actions', 'label' => '', 'class' => 'text-right']
            ]" 
            :rows="$classrooms"
        >
            @scope('cell_name', $classroom)
                <button
                    type="button"
                    wire:click="viewDetail({{ $classroom->id }})"
                    class="font-bold text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors text-left"
                >
                    {{ $classroom->name }}
                </button>
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

            @scope('cell_students_count', $classroom)
                <button
                    type="button"
                    wire:click="viewDetail({{ $classroom->id }})"
                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 hover:bg-indigo-50 text-slate-700 hover:text-indigo-700 dark:bg-slate-800 dark:hover:bg-indigo-900/30 dark:text-slate-300 dark:hover:text-indigo-300 transition-colors"
                >
                    <x-ui.icon name="o-users" class="size-3.5" />
                    <span>{{ $classroom->students_count }}</span>
                </button>
            @endscope

            @scope('cell_actions', $classroom)
                <div class="flex justify-end gap-1">
                    <x-ui.button icon="o-eye" wire:click="viewDetail({{ $classroom->id }})" ghost wire:loading.attr="disabled" />
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

    <x-ui.modal wire:model="copyModal" persistent>
        <x-ui.header :title="__('Salin Kelas Antar Tahun Ajaran')" :subtitle="__('Duplikasi daftar rombongan belajar dari tahun ajaran sebelumnya.')" separator />

        <form wire:submit="copyClassrooms" class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-ui.select
                    wire:model="copy_source_year_id"
                    :label="__('Dari Tahun Ajaran (Asal)')"
                    :options="$years"
                    :placeholder="__('Pilih Tahun Asal')"
                    required
                />
                <x-ui.select
                    wire:model="copy_target_year_id"
                    :label="__('Ke Tahun Ajaran (Tujuan)')"
                    :options="$years"
                    :placeholder="__('Pilih Tahun Tujuan')"
                    required
                />
            </div>

            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-3">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">
                    {{ __('Opsi Penyalinan') }}
                </span>
                <x-ui.checkbox wire:model="copy_homeroom" :label="__('Sertakan Wali Kelas (Homeroom Teacher)')" />
                <x-ui.checkbox wire:model="copy_assignments" :label="__('Sertakan Penugasan Guru Mata Pelajaran & Kelas')" />
            </div>

            <div class="flex justify-end gap-2 pt-4">
                <x-ui.button :label="__('Batal')" ghost @click="show = false" />
                <x-ui.button :label="__('Salin Kelas')" icon="o-document-duplicate" type="submit" class="btn-primary" spinner="copyClassrooms" />
            </div>
        </form>
    </x-ui.modal>

    <x-ui.modal wire:model="detailModal" maxWidth="max-w-3xl">
        @if($viewingClassroom)
            @php
                $classTeachers = $viewingClassroom->teacherAssignments->where('type', 'class_teacher');
                $subjectAssignments = $viewingClassroom->teacherAssignments->where('type', 'subject_teacher');
                $homeroomName = $viewingClassroom->homeroomTeacher?->name
                    ?? $classTeachers->first()?->teacher?->name
                    ?? __('Belum Ditentukan');
                $studentsList = $viewingClassroom->students
                    ->filter(fn($sp) => $sp->profile?->user !== null)
                    ->sortBy(fn($sp) => $sp->profile->user->name);
            @endphp

            <x-ui.header
                :title="$viewingClassroom->name"
                :subtitle="$viewingClassroom->level->name . ' • ' . __('Tahun Ajaran') . ' ' . $viewingClassroom->academicYear->name"
                separator
            />

            <div class="space-y-6">
                {{-- Info Ringkas & Wali Kelas --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1">{{ __('Wali Kelas / Guru Kelas') }}</span>
                        <div class="flex items-center gap-2">
                            <x-ui.icon name="o-user" class="size-4 text-indigo-500 shrink-0" />
                            <span class="font-bold text-slate-900 dark:text-white text-sm">{{ $homeroomName }}</span>
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1">{{ __('Tingkat & Fase') }}</span>
                        <span class="font-bold text-slate-900 dark:text-white text-sm">
                            {{ $viewingClassroom->class_level !== null ? __('Kelas :level', ['level' => $viewingClassroom->class_level]) : '-' }}
                            @if($viewingClassroom->getPhase())
                                ({{ $viewingClassroom->phase_label }})
                            @endif
                        </span>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1">{{ __('Total Siswa') }}</span>
                        <span class="font-bold text-slate-900 dark:text-white text-sm">
                            {{ $studentsList->count() }} {{ __('Siswa') }}
                        </span>
                    </div>
                </div>

                {{-- Daftar Guru Pengampu --}}
                <div class="space-y-2">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        {{ __('Guru Pengampu Mata Pelajaran') }} ({{ $subjectAssignments->count() }})
                    </h4>
                    @if($subjectAssignments->isNotEmpty())
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 max-h-48 overflow-y-auto pr-1">
                            @foreach($subjectAssignments as $assignment)
                                <div class="flex items-center justify-between p-3 rounded-xl border border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900">
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-slate-900 dark:text-white truncate">
                                            {{ $assignment->subject?->name ?? $assignment->diniyahSubject?->name ?? __('Mata Pelajaran') }}
                                        </p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                            {{ $assignment->teacher?->name ?? '-' }}
                                        </p>
                                    </div>
                                    @if($assignment->diniyah_subject_id)
                                        <x-ui.badge :label="__('Diniyah')" class="bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 text-[10px]" />
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-4 rounded-xl border border-dashed border-slate-200 dark:border-slate-800 text-center text-xs text-slate-400 italic">
                            {{ __('Belum ada penugasan guru mata pelajaran di kelas ini.') }}
                        </div>
                    @endif
                </div>

                {{-- Daftar Siswa --}}
                <div class="space-y-2">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        {{ __('Daftar Siswa') }} ({{ $studentsList->count() }})
                    </h4>
                    @if($studentsList->isNotEmpty())
                        <div class="max-h-64 overflow-y-auto rounded-xl border border-slate-100 dark:border-slate-800 divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($studentsList as $studentProfile)
                                @php $studentUser = $studentProfile->profile->user; @endphp
                                <div class="flex items-center justify-between px-4 py-2.5 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="size-7 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xs font-bold shrink-0">
                                            {{ substr($studentUser->name, 0, 1) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">{{ $studentUser->name }}</p>
                                            <p class="text-[11px] text-slate-400 font-mono">
                                                NIS: {{ $studentProfile->nis ?? '-' }} • NISN: {{ $studentProfile->nisn ?? '-' }}
                                            </p>
                                        </div>
                                    </div>
                                    <x-ui.badge :label="ucfirst($studentProfile->status ?? 'aktif')" class="bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 text-[10px]" />
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-6 rounded-xl border border-dashed border-slate-200 dark:border-slate-800 text-center text-xs text-slate-400 italic">
                            {{ __('Belum ada siswa yang ditempatkan di kelas ini.') }}
                        </div>
                    @endif
                </div>

                <div class="flex justify-end pt-2 border-t border-slate-100 dark:border-slate-800">
                    <x-ui.button :label="__('Tutup')" ghost @click="show = false" />
                </div>
            </div>
        @endif
    </x-ui.modal>
</div>
