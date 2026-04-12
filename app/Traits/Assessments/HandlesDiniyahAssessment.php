<?php

namespace App\Traits\Assessments;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\DiniyahGrade;
use App\Models\DiniyahSubject;
use App\Models\User;
use Illuminate\Support\Facades\DB;

trait HandlesDiniyahAssessment
{
    use \App\Traits\HasAssessmentLogic;

    public ?int $academic_year_id = null;

    public ?int $classroom_id = null;

    public ?int $diniyah_subject_id = null;

    public string $semester = '1';

    // Data containers
    public array $grades_data = [];

    public function mountHandlesDiniyahAssessment(): void
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if ($activeYear) {
            $this->academic_year_id = $activeYear->id;
        }
    }

    public function updatedClassroomId(): void
    {
        $this->loadGrades();

        // Reset subject if not valid for the new classroom level
        if ($this->diniyah_subject_id && $this->classroom_id) {
            $classroom = Classroom::find($this->classroom_id);
            $isValid = DiniyahSubject::where('id', $this->diniyah_subject_id)
                ->where('level_id', $classroom?->level_id)
                ->exists();

            if (! $isValid) {
                $this->diniyah_subject_id = null;
            }
        }
    }

    public function updatedDiniyahSubjectId(): void
    {
        $this->loadGrades();
    }

    public function updatedSemester(): void
    {
        $this->loadGrades();
    }

    public function loadGrades(): void
    {
        if (! $this->classroom_id || ! $this->diniyah_subject_id || ! $this->academic_year_id) {
            $this->grades_data = [];

            return;
        }

        // Security check for Guru
        if (auth()->user()->isGuru() && (! auth()->user()->hasAccessToClassroom((int) $this->classroom_id) || ! auth()->user()->hasAccessToDiniyahSubject((int) $this->diniyah_subject_id))) {
            $this->grades_data = [];
            $this->dispatch('toast', type: 'error', message: 'Anda tidak memiliki akses ke data ini.');

            return;
        }

        $grades = DiniyahGrade::where([
            'classroom_id' => $this->classroom_id,
            'diniyah_subject_id' => $this->diniyah_subject_id,
            'academic_year_id' => $this->academic_year_id,
            'semester' => $this->semester,
        ])->get();

        $scores = [];
        foreach ($grades as $grade) {
            $scores[$grade->student_id] = [
                'knowledge_grade' => $grade->knowledge_grade,
                'practice_grade' => $grade->practice_grade,
                'attitude_grade' => $grade->attitude_grade,
                'achievement' => $grade->achievement,
                'grade' => $grade->grade,
                'notes' => $grade->notes,
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
            if (! isset($scores[$student->id])) {
                $scores[$student->id] = [
                    'knowledge_grade' => null,
                    'practice_grade' => null,
                    'attitude_grade' => 'B', // Default to B for attitude
                    'achievement' => '',
                    'grade' => null,
                    'notes' => '',
                ];
            }
        }

        $this->grades_data = $scores;
    }

    public function save(): void
    {
        if (! $this->canEditAssessments()) {
            $this->dispatch('toast', type: 'error', message: 'Anda tidak memiliki izin untuk menyimpan data.');

            return;
        }

        if (! $this->classroom_id || ! $this->diniyah_subject_id || ! $this->academic_year_id) {
            return;
        }

        // Security check for Guru
        if (auth()->user()->isGuru() && (! auth()->user()->hasAccessToClassroom((int) $this->classroom_id) || ! auth()->user()->hasAccessToDiniyahSubject((int) $this->diniyah_subject_id))) {
            $this->dispatch('toast', type: 'error', message: 'Akses ditolak.');

            return;
        }

        $subject = DiniyahSubject::find($this->diniyah_subject_id);
        if (! $subject) {
            return;
        }

        DB::transaction(function () use ($subject) {
            foreach ($this->grades_data as $studentId => $data) {
                $values = [
                    'classroom_id' => $this->classroom_id,
                    'academic_year_id' => $this->academic_year_id,
                    'semester' => $this->semester,
                ];

                if ($subject->assessment_type === 'numeric') {
                    $values['knowledge_grade'] = $data['knowledge_grade'] !== '' ? (float) $data['knowledge_grade'] : null;
                    if ($subject->has_practice) {
                        $values['practice_grade'] = $data['practice_grade'] !== '' ? (float) $data['practice_grade'] : null;
                    }
                    $values['attitude_grade'] = $data['attitude_grade'] ?: 'B';
                } else {
                    $values['achievement'] = $data['achievement'] ?: null;
                    $values['grade'] = $data['grade'] !== '' ? (float) $data['grade'] : null;
                    $values['notes'] = $data['notes'] ?: null;
                }

                DiniyahGrade::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'diniyah_subject_id' => $this->diniyah_subject_id,
                        'classroom_id' => $this->classroom_id,
                        'academic_year_id' => $this->academic_year_id,
                        'semester' => $this->semester,
                    ],
                    $values
                );
            }
        });

        $this->dispatch('toast', type: 'success', message: 'Data penilaian diniyah berhasil disimpan.');
    }

    public function getFilteredDiniyahSubjects(?int $classroomId = null)
    {
        $user = auth()->user();
        $classroomId = $classroomId ?: $this->classroom_id;

        return DiniyahSubject::query()
            ->when($classroomId, function ($query) use ($classroomId) {
                $classroom = Classroom::find($classroomId);
                if ($classroom) {
                    $query->where('level_id', $classroom->level_id);
                }
            })
            ->when($user->isGuru(), function ($query) use ($user, $classroomId) {
                $classroom = Classroom::find($classroomId);
                // If Paket A (class teacher system), and teacher is homeroom, allow all.
                // Otherwise, only assigned.
                if ($classroom && $classroom->level && $classroom->level->isClassTeacherSystem()) {
                    if (! $user->hasAccessToClassroom($classroomId)) {
                        $query->whereRaw('1 = 0'); // Deny if not homeroom
                    }
                } else {
                    $query->whereIn('id', $user->assignedDiniyahSubjects()->pluck('diniyah_subjects.id'));
                }
            })
            ->orderBy('name')
            ->get();
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
            'classrooms' => $this->getFilteredClassrooms(), // Already filtered by education level if needed elsewhere?
            'classroomsKesetaraan' => Classroom::whereHas('level', fn ($q) => $q->whereIn('education_level', ['sd', 'smp', 'sma']))
                ->when(auth()->user()->isGuru(), fn ($q) => $q->whereIn('id', auth()->user()->getAssignedClassroomIds()))
                ->orderBy('name')
                ->get(),
            'diniyahSubjects' => $this->getFilteredDiniyahSubjects(),
            'students' => $students,
            'currentDiniyahSubject' => $this->diniyah_subject_id ? DiniyahSubject::find($this->diniyah_subject_id) : null,
            'isReadonly' => ! $this->canEditAssessments(),
        ];
    }
}
