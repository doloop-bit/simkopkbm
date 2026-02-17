<?php

namespace App\Traits\Assessments;

use App\Models\User;
use App\Models\SubjectGrade;
use App\Models\SubjectTp;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\AcademicYear;
use App\Models\LearningAchievement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait HandlesGradingAssessment
{
    // Needs HasAssessmentLogic for generic helpers
    use \App\Traits\HasAssessmentLogic;

    public ?int $academic_year_id = null;
    public ?int $classroom_id = null;
    public ?int $subject_id = null;
    public string $semester = '1';

    // Data containers
    public array $grades_data = []; // [student_id => ['grade' => float, 'best_tp_ids' => [], 'improvement_tp_ids' => []]]

    // Phase info for display
    public ?string $currentPhase = null;

    // TP Selection Modal State
    public bool $showTpModal = false;
    public ?int $editingStudentId = null;
    public ?string $editingType = null; // 'best' or 'improvement'
    public ?string $editingStudentName = null;
    public array $tempSelectedTps = [];

    public function mountHandlesGradingAssessment(): void
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
                    $phase = $classroom->getPhase();
                    $q->whereNull('phase');
                    if ($phase) {
                        $q->orWhere('phase', $phase);
                    }
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
                'best_tp_ids' => $grade->best_tp_ids ?? [],
                'improvement_tp_ids' => $grade->improvement_tp_ids ?? [],
            ];
        }

        // Ensure all students in classroom have an entry
        $students = User::where('role', 'siswa')
            ->whereHas('profiles', function ($q) {
                $q->whereHasMorph('profileable', [\App\Models\StudentProfile::class], function ($q) {
                    $q->where('classroom_id', $this->classroom_id);
                });
            })->get();

        foreach ($students as $student) {
            if (!isset($scores[$student->id])) {
                $scores[$student->id] = [
                    'grade' => null,
                    'best_tp_ids' => [],
                    'improvement_tp_ids' => [],
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
            if (!empty($data['best_tp_ids']) && !empty($data['improvement_tp_ids'])) {
                if (array_intersect($data['best_tp_ids'], $data['improvement_tp_ids'])) {
                    $studentName = User::find($studentId)?->name ?? 'Siswa';
                    \Flux::toast(variant: 'danger', text: "TP yang sama tidak boleh dipilih sebagai Terbaik dan Perlu Peningkatan sekaligus untuk $studentName.");
                    return;
                }
            }
        }

        DB::transaction(function () {
            foreach ($this->grades_data as $studentId => $data) {
                $hasGrade = isset($data['grade']) && $data['grade'] !== '';
                $hasBestTp = !empty($data['best_tp_ids']);
                $hasImpTp = !empty($data['improvement_tp_ids']);

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
                        'best_tp_ids' => $data['best_tp_ids'] ?: null,
                        'improvement_tp_ids' => $data['improvement_tp_ids'] ?: null,
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

    public function openTpSelection($studentId, $type)
    {
        $this->editingStudentId = $studentId;
        $this->editingType = $type;
        $this->editingStudentName = User::find($studentId)?->name;
        
        $key = $type . '_tp_ids';
        $this->tempSelectedTps = $this->grades_data[$studentId][$key] ?? [];
        
        $this->showTpModal = true;
    }

    public function saveTpSelection()
    {
        if ($this->editingStudentId && $this->editingType) {
            $key = $this->editingType . '_tp_ids';
            $this->grades_data[$this->editingStudentId][$key] = $this->tempSelectedTps;
            
            // Validate intersection immediately for better UX
            $otherType = $this->editingType === 'best' ? 'improvement' : 'best';
            $otherKey = $otherType . '_tp_ids';
            $otherTps = $this->grades_data[$this->editingStudentId][$otherKey] ?? [];
            
            if (array_intersect($this->tempSelectedTps, $otherTps)) {
                // TP overlap detected - still save but could warn
            }
        }
        
        $this->showTpModal = false;
        $this->reset(['editingStudentId', 'editingType', 'editingStudentName', 'tempSelectedTps']);
    }

    public function with(): array
    {
        $students = [];
        if ($this->classroom_id) {
            $students = User::where('role', 'siswa')
                ->whereHas('profiles', function ($q) {
                    $q->whereHasMorph('profileable', [\App\Models\StudentProfile::class], function ($q) {
                        $q->where('classroom_id', $this->classroom_id);
                    });
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
}
