<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\SubjectGrade;
use App\Models\SubjectTp;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\AcademicYear;
use App\Models\LearningAchievement;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

new #[Layout('components.admin.layouts.app')] class extends Component {
    public ?int $academic_year_id = null;
    public ?int $classroom_id = null;
    public ?int $subject_id = null;
    public string $semester = '1';

    // Data containers
    public array $grades_data = []; // [student_id => ['grade' => float, 'best_tp_id' => int, 'improvement_tp_id' => int]]

    // Phase info for display
    public ?string $currentPhase = null;

    public function mount(): void
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if ($activeYear) {
            $this->academic_year_id = $activeYear->id;
        }
    }

    public function updatedClassroomId(): void
    {
        $this->resolvePhase();
        $this->loadGrades();

        // Reset subject if it's not valid for the new classroom
        if ($this->subject_id && $this->classroom_id) {
            $classroom = Classroom::find($this->classroom_id);
            $isValid = Subject::where('id', $this->subject_id)
                ->where(function ($q) use ($classroom) {
                    $q->whereNull('level_id')
                        ->orWhere('level_id', $classroom->level_id);
                })->exists();

            if (!$isValid) {
                $this->subject_id = null;
            }
        }
    }

    public function updatedSubjectId(): void
    {
        $this->loadGrades();
    }

    public function updatedSemester(): void
    {
        $this->loadGrades();
    }

    /**
     * Resolve the Kurikulum Merdeka phase for the selected classroom.
     */
    public function resolvePhase(): void
    {
        if (!$this->classroom_id) {
            $this->currentPhase = null;
            return;
        }

        $classroom = Classroom::with('level')->find($this->classroom_id);
        $this->currentPhase = $classroom?->getPhase();
    }

    public function loadGrades(): void
    {
        if (!$this->classroom_id || !$this->subject_id || !$this->academic_year_id) {
            $this->grades_data = [];
            return;
        }

        $grades = SubjectGrade::where([
            'classroom_id' => $this->classroom_id,
            'subject_id' => $this->subject_id,
            'academic_year_id' => $this->academic_year_id,
            'semester' => $this->semester,
        ])->get();

        $scores = [];
        foreach ($grades as $grade) {
            $scores[$grade->student_id] = [
                'grade' => $grade->grade,
                'best_tp_id' => $grade->best_tp_id,
                'improvement_tp_id' => $grade->improvement_tp_id,
            ];
        }

        // Ensure all students in classroom have an entry
        $students = User::where('role', 'siswa')
            ->whereHas('profiles.profileable', function ($q) {
                $q->where('classroom_id', $this->classroom_id);
            })->get();

        foreach ($students as $student) {
            if (!isset($scores[$student->id])) {
                $scores[$student->id] = [
                    'grade' => null,
                    'best_tp_id' => null,
                    'improvement_tp_id' => null,
                ];
            }
        }

        $this->grades_data = $scores;
    }

    public function save(): void
    {
        if (!$this->classroom_id || !$this->subject_id || !$this->academic_year_id) {
            return;
        }

        // Validate duplicates locally
        foreach ($this->grades_data as $studentId => $data) {
            if (!empty($data['best_tp_id']) && !empty($data['improvement_tp_id'])) {
                if ($data['best_tp_id'] == $data['improvement_tp_id']) {
                    $studentName = User::find($studentId)?->name ?? 'Siswa';
                    \Flux::toast(variant: 'danger', text: "TP Terbaik dan TP Perlu Peningkatan tidak boleh sama untuk $studentName.");
                    return;
                }
            }
        }

        DB::transaction(function () {
            foreach ($this->grades_data as $studentId => $data) {
                $hasGrade = isset($data['grade']) && $data['grade'] !== '';
                $hasBestTp = !empty($data['best_tp_id']);
                $hasImpTp = !empty($data['improvement_tp_id']);

                if (!$hasGrade && !$hasBestTp && !$hasImpTp)
                    continue;

                SubjectGrade::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'subject_id' => $this->subject_id,
                        'classroom_id' => $this->classroom_id,
                        'academic_year_id' => $this->academic_year_id,
                        'semester' => $this->semester,
                    ],
                    [
                        'grade' => $hasGrade ? (float) $data['grade'] : 0,
                        'best_tp_id' => $data['best_tp_id'] ?: null,
                        'improvement_tp_id' => $data['improvement_tp_id'] ?: null,
                    ]
                );
            }
        });

        \Flux::toast('Data penilaian rapor berhasil disimpan.');
    }

    /**
     * Get TPs filtered by the classroom's phase.
     * This ensures only TPs relevant to the current fase are shown.
     */
    public function getFilteredTps()
    {
        if (!$this->subject_id) {
            return collect();
        }

        // If we have a phase, filter TPs by that phase via learning_achievements
        if ($this->currentPhase) {
            $cp = LearningAchievement::where('subject_id', $this->subject_id)
                ->where('phase', $this->currentPhase)
                ->first();

            if ($cp) {
                return $cp->tps()->orderBy('code')->get();
            }

            return collect();
        }

        // Fallback: if no phase resolved, show all TPs for the subject
        return SubjectTp::whereHas('learningAchievement', function ($q) {
            $q->where('subject_id', $this->subject_id);
        })->orderBy('code')->get();
    }

    public function with(): array
    {
        $students = [];

        if ($this->classroom_id) {
            $students = User::where('role', 'siswa')
                ->whereHas('profiles.profileable', function ($q) {
                    $q->where('classroom_id', $this->classroom_id);
                })
                ->orderBy('name')
                ->get();
        }

        $tps = $this->getFilteredTps();
        $user = auth()->user();

        $subjects = Subject::orderBy('name')
            ->when($this->classroom_id, function ($query) {
                $classroom = Classroom::find($this->classroom_id);
                if ($classroom) {
                    $query->where(function ($q) use ($classroom) {
                        $q->whereNull('level_id')
                            ->orWhere('level_id', $classroom->level_id);
                    });
                }
            })
            ->when(!$user->isAdmin(), function ($query) use ($user) {
                $query->whereIn('id', $user->getAssignedSubjectIds());
            })
            ->get();

        $classroomsQuery = Classroom::with('level')
            ->when($this->academic_year_id, fn($q) => $q->where('academic_year_id', $this->academic_year_id));

        if (!$user->isAdmin()) {
            $classroomsQuery->whereIn('id', $user->getAssignedClassroomIds());
        }

        $classrooms = $classroomsQuery->get();

        return [
            'years' => AcademicYear::all(),
            'classrooms' => $classrooms,
            'subjects' => $subjects,
            'students' => $students,
            'tps' => $tps,
        ];
    }
}; ?>

<div class="pb-24 md:pb-0">
    {{-- Navigation Component --}}
    <x-admin.report-card-nav />

    <div class="flex items-center justify-between mb-4">
        <div>
            <flux:heading size="xl" level="1">Nilai Rapor Subjek & TP</flux:heading>
            <flux:subheading>Input nilai akhir dan deskripsi TP untuk rapor.</flux:subheading>
        </div>
    </div>

    {{-- Compact Usage Guide --}}
    <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-950/30 rounded-lg border border-blue-200 dark:border-blue-800">
        <div class="flex items-start gap-2">
            <flux:icon icon="information-circle"
                class="w-4 h-4 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" />
            <div class="flex-1 min-w-0">
                <p class="text-xs font-medium text-blue-900 dark:text-blue-200 mb-1.5">
                    Panduan: <span class="hidden md:inline">Gunakan navigasi di atas</span><span
                        class="md:hidden">Gunakan navigasi di bawah</span> untuk beralih menu
                </p>
                <div class="flex flex-wrap items-center gap-1.5 text-[11px]">
                    <div
                        class="flex items-center gap-1 px-2 py-0.5 bg-white dark:bg-zinc-800 rounded border border-blue-200 dark:border-blue-700">
                        <span
                            class="flex-shrink-0 w-3.5 h-3.5 bg-blue-600 text-white rounded-full flex items-center justify-center text-[9px] font-bold">1</span>
                        <span class="text-zinc-700 dark:text-zinc-300 whitespace-nowrap">Input Nilai & TP</span>
                    </div>
                    <flux:icon icon="arrow-right" class="w-3 h-3 text-blue-400 dark:text-blue-500 flex-shrink-0" />
                    <div
                        class="flex items-center gap-1 px-2 py-0.5 bg-white dark:bg-zinc-800 rounded border border-green-200 dark:border-green-700">
                        <span
                            class="flex-shrink-0 w-3.5 h-3.5 bg-green-600 text-white rounded-full flex items-center justify-center text-[9px] font-bold">2</span>
                        <span class="text-zinc-700 dark:text-zinc-300 whitespace-nowrap">Input Kehadiran</span>
                    </div>
                    <flux:icon icon="arrow-right" class="w-3 h-3 text-blue-400 dark:text-blue-500 flex-shrink-0" />
                    <div
                        class="flex items-center gap-1 px-2 py-0.5 bg-white dark:bg-zinc-800 rounded border border-purple-200 dark:border-purple-700">
                        <span
                            class="flex-shrink-0 w-3.5 h-3.5 bg-purple-600 text-white rounded-full flex items-center justify-center text-[9px] font-bold">3</span>
                        <span class="text-zinc-700 dark:text-zinc-300 whitespace-nowrap">Buat Rapor</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <flux:select wire:model.live="academic_year_id" label="Tahun Ajaran">
            @foreach($years as $year)
                <option value="{{ $year->id }}">{{ $year->name }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="semester" label="Semester">
            <option value="1">1 (Ganjil)</option>
            <option value="2">2 (Genap)</option>
        </flux:select>

        <flux:select wire:model.live="classroom_id" label="Kelas">
            <option value="">Pilih Kelas</option>
            @foreach($classrooms as $room)
                <option value="{{ $room->id }}">
                    {{ $room->name }}
                    @if($room->class_level && $room->getPhase())
                        (Fase {{ $room->getPhase() }})
                    @endif
                </option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="subject_id" label="Mata Pelajaran">
            <option value="">Pilih Mata Pelajaran</option>
            @foreach($subjects as $subject)
                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
            @endforeach
        </flux:select>
    </div>

    {{-- Phase indicator --}}
    @if($currentPhase)
        <div class="mb-4 flex items-center gap-2">
            <flux:badge color="indigo">Fase {{ $currentPhase }}</flux:badge>
            <span class="text-sm text-zinc-500 dark:text-zinc-400">
                TP yang ditampilkan sesuai dengan fase kelas yang dipilih.
            </span>
        </div>
    @endif

    @if($classroom_id && $subject_id)
        @if($tps->isEmpty())
            <div class="mb-4 p-4 rounded-lg border border-amber-200 bg-amber-50 dark:bg-amber-950/30 dark:border-amber-900">
                <div class="flex items-center gap-2">
                    <flux:icon icon="exclamation-triangle" class="w-5 h-5 text-amber-600" />
                    <p class="text-sm text-amber-700 dark:text-amber-400">
                        Belum ada TP untuk mata pelajaran ini
                        @if($currentPhase)
                            pada Fase {{ $currentPhase }}
                        @endif
                        . Silakan tambahkan TP melalui menu <strong>Mata Pelajaran → Kelola CP & TP</strong>.
                    </p>
                </div>
            </div>
        @endif

        <div class="border rounded-lg bg-white dark:bg-zinc-900 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border-collapse min-w-[800px]">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b w-64">Nama Siswa</th>
                            <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b w-32 text-center">
                                Nilai Final (0-100)</th>
                            <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">TP Tertinggi
                                (Kompeten)</th>
                            <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">TP Terendah (Perlu
                                Bimbingan)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($students as $student)
                            <tr wire:key="{{ $student->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-4 py-3 text-zinc-900 dark:text-white font-medium align-top">
                                    {{ $student->name }}
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <flux:input wire:model="grades_data.{{ $student->id }}.grade" type="number" min="0"
                                        max="100" step="1" class="text-center" />
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="w-full space-y-1">
                                        <select wire:model="grades_data.{{ $student->id }}.best_tp_id"
                                            class="block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:bg-zinc-900 dark:text-white dark:ring-zinc-700">
                                            <option value="">-- Pilih TP Terbaik --</option>
                                            @foreach($tps as $tp)
                                                <option value="{{ $tp->id }}">
                                                    {{ $tp->code ? $tp->code . ' - ' : '' }}{{ str($tp->description)->limit(60) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if(!empty($grades_data[$student->id]['best_tp_id']))
                                            <p class="text-xs text-green-600 dark:text-green-400 italic">
                                                {{ $tps->find($grades_data[$student->id]['best_tp_id'])?->description }}
                                            </p>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="w-full space-y-1">
                                        <select wire:model="grades_data.{{ $student->id }}.improvement_tp_id"
                                            class="block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:bg-zinc-900 dark:text-white dark:ring-zinc-700">
                                            <option value="">-- Pilih TP Perlu Bimbingan --</option>
                                            @foreach($tps as $tp)
                                                <option value="{{ $tp->id }}">
                                                    {{ $tp->code ? $tp->code . ' - ' : '' }}{{ str($tp->description)->limit(60) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if(!empty($grades_data[$student->id]['improvement_tp_id']))
                                            <p class="text-xs text-orange-600 dark:text-orange-400 italic">
                                                {{ $tps->find($grades_data[$student->id]['improvement_tp_id'])?->description }}
                                            </p>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 bg-zinc-50 dark:bg-zinc-800 border-t flex justify-end sticky bottom-0 z-10">
                <flux:button variant="primary" icon="check" wire:click="save">Simpan Penilaian Rapor</flux:button>
            </div>
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-12 text-zinc-500 border-2 border-dashed rounded-xl">
            <svg class="w-12 h-12 mb-2 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            <p>Silakan pilih kelas dan mata pelajaran untuk memulai penilaian rapor.</p>
        </div>
    @endif
</div>