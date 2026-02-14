<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\SubjectGrade;
use App\Models\SubjectTp;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\AcademicYear;
use App\Models\LearningAchievement;
use App\Traits\HasAssessmentLogic;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

new class extends Component {
    use HasAssessmentLogic;

    public ?int $academic_year_id = null;
    public ?int $classroom_id = null;
    public ?int $subject_id = null;
    public string $semester = '1';

    // Data containers
    public array $grades_data = []; // [student_id => ['grade' => float, 'best_tp_id' => int, 'improvement_tp_id' => int]]

    // Phase info for display
    public ?string $currentPhase = null;

    public function layout()
    {
        return $this->getLayout();
    }

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

        // Security check for Guru
        if (auth()->user()->isGuru() && (!auth()->user()->hasAccessToClassroom((int)$this->classroom_id) || !auth()->user()->hasAccessToSubject((int)$this->subject_id))) {
             $this->grades_data = [];
             \Flux::toast(variant: 'danger', text: 'Anda tidak memiliki akses ke data ini.');
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
        if (!$this->canEditAssessments()) {
            \Flux::toast(variant: 'danger', text: 'Anda tidak memiliki izin untuk menyimpan data.');
            return;
        }

        if (!$this->classroom_id || !$this->subject_id || !$this->academic_year_id) {
            return;
        }

        // Security check for Guru
        if (auth()->user()->isGuru() && (!auth()->user()->hasAccessToClassroom((int)$this->classroom_id) || !auth()->user()->hasAccessToSubject((int)$this->subject_id))) {
            \Flux::toast(variant: 'danger', text: 'Akses ditolak.');
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

    public function getFilteredTps()
    {
        if (!$this->subject_id) {
            return collect();
        }

        if ($this->currentPhase) {
            $cp = LearningAchievement::where('subject_id', $this->subject_id)
                ->where('phase', $this->currentPhase)
                ->first();

            if ($cp) {
                return $cp->tps()->orderBy('code')->get();
            }

            return collect();
        }

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

        return [
            'years' => AcademicYear::all(),
            'classrooms' => $this->getFilteredClassrooms(),
            'subjects' => $this->getFilteredSubjects($this->classroom_id),
            'students' => $students,
            'tps' => $this->getFilteredTps(),
            'isReadonly' => !$this->canEditAssessments(),
        ];
    }
}; ?>

<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">{{ __('Penilaian Rapor (Nilai & TP)') }}</flux:heading>
            <flux:subheading>{{ __('Input nilai akhir dan pemilihan TP kompetensi untuk rapor.') }}</flux:subheading>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <flux:select wire:model.live="academic_year_id" label="Tahun Ajaran" :disabled="$isReadonly">
            @foreach($years as $year)
                <option value="{{ $year->id }}">{{ $year->name }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="semester" label="Semester" :disabled="$isReadonly">
            <option value="1">1 (Ganjil)</option>
            <option value="2">2 (Genap)</option>
        </flux:select>

        <flux:select wire:model.live="classroom_id" label="Kelas" :disabled="$isReadonly">
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

        <flux:select wire:model.live="subject_id" label="Mata Pelajaran" :disabled="$isReadonly">
            <option value="">Pilih Mata Pelajaran</option>
            @foreach($subjects as $subject)
                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
            @endforeach
        </flux:select>
    </div>

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
                        . Silakan tambahkan TP melalui menu <strong>Mata Pelajaran (Admin)</strong>.
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
                            <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b w-32 text-center">Nilai Final (0-100)</th>
                            <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">TP Tertinggi (Kompeten)</th>
                            <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">TP Terendah (Perlu Bimbingan)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($students as $student)
                            <tr wire:key="{{ $student->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-4 py-3 text-zinc-900 dark:text-white font-medium align-top">
                                    {{ $student->name }}
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <flux:input 
                                        wire:model="grades_data.{{ $student->id }}.grade" 
                                        type="number" 
                                        min="0"
                                        max="100" 
                                        step="1" 
                                        class="text-center" 
                                        :readonly="$isReadonly"
                                    />
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="w-full space-y-1">
                                        <select wire:model="grades_data.{{ $student->id }}.best_tp_id" :disabled="$isReadonly"
                                            class="block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:bg-zinc-900 dark:text-white dark:ring-zinc-700">
                                            <option value="">-- Pilih TP Terbaik --</option>
                                            @foreach($tps as $tp)
                                                <option value="{{ $tp->id }}">
                                                    {{ $tp->code ? $tp->code . ' - ' : '' }}{{ Str::limit($tp->description, 60) }}
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
                                        <select wire:model="grades_data.{{ $student->id }}.improvement_tp_id" :disabled="$isReadonly"
                                            class="block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:bg-zinc-900 dark:text-white dark:ring-zinc-700">
                                            <option value="">-- Pilih TP Perlu Bimbingan --</option>
                                            @foreach($tps as $tp)
                                                <option value="{{ $tp->id }}">
                                                    {{ $tp->code ? $tp->code . ' - ' : '' }}{{ Str::limit($tp->description, 60) }}
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

            @if(!$isReadonly)
                <div class="p-4 bg-zinc-50 dark:bg-zinc-800 border-t flex justify-end sticky bottom-0 z-10">
                    <flux:button variant="primary" icon="check" wire:click="save">Simpan Penilaian Rapor</flux:button>
                </div>
            @endif
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-12 text-zinc-500 border-2 border-dashed rounded-xl">
            <flux:icon icon="pencil-square" class="w-12 h-12 mb-2 opacity-20" />
            <p>Silakan pilih kelas dan mata pelajaran untuk memulai penilaian rapor.</p>
        </div>
    @endif
</div>
