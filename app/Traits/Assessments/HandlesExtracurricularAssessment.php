<?php

namespace App\Traits\Assessments;

use App\Models\User;
use App\Models\Classroom;
use App\Models\AcademicYear;
use App\Models\ExtracurricularActivity;
use App\Models\ExtracurricularAssessment;
use Illuminate\Support\Facades\DB;

trait HandlesExtracurricularAssessment
{
    use \App\Traits\HasAssessmentLogic;

    public ?int $academic_year_id = null;
    public ?int $classroom_id = null;
    public ?int $activity_id = null;
    public string $semester = '1';

    public array $assessments_data = []; // [student_id => ['level' => '', 'description' => '']]

    public function mountHandlesExtracurricularAssessment(): void
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if ($activeYear) {
            $this->academic_year_id = $activeYear->id;
        }
    }

    public function updatedClassroomId(): void
    {
        $this->loadAssessments();
    }

    public function updatedActivityId(): void
    {
        $this->loadAssessments();
    }

    public function updatedSemester(): void
    {
        $this->loadAssessments();
    }

    public function loadAssessments(): void
    {
        if (!$this->classroom_id || !$this->activity_id) {
            $this->assessments_data = [];
            return;
        }

        // Security check for Guru
        if (auth()->user()->isGuru() && !auth()->user()->hasAccessToClassroom((int)$this->classroom_id)) {
             $this->assessments_data = [];
             \Flux::toast(variant: 'danger', text: 'Anda tidak memiliki akses ke kelas ini.');
             return;
        }

        // Load existing assessments
        $assessments = ExtracurricularAssessment::where([
            'extracurricular_activity_id' => $this->activity_id,
            'academic_year_id' => $this->academic_year_id,
            'semester' => $this->semester,
        ])->get();

        $this->assessments_data = $assessments->mapWithKeys(function ($assessment) {
            return [
                $assessment->student_id => [
                    'level' => $assessment->achievement_level,
                    'description' => $assessment->description ?? '',
                ]
            ];
        })->toArray();
        
        // Ensure all students in classroom have an entry
        $students = User::where('role', 'siswa')
            ->whereHas('profiles', function ($q) {
                $q->whereHasMorph('profileable', [\App\Models\StudentProfile::class], function ($q) {
                    $q->where('classroom_id', $this->classroom_id);
                });
            })->get();

        foreach ($students as $student) {
            if (!isset($this->assessments_data[$student->id])) {
                $this->assessments_data[$student->id] = [
                    'level' => 'Baik',
                    'description' => '',
                ];
            }
        }
    }

    public function save(): void
    {
        if (!$this->canEditAssessments()) {
            \Flux::toast(variant: 'danger', text: 'Anda tidak memiliki izin untuk menyimpan data.');
            return;
        }

        if (!$this->classroom_id || !$this->activity_id || !$this->academic_year_id) {
            return;
        }

        // Security check for Guru
        if (auth()->user()->isGuru() && !auth()->user()->hasAccessToClassroom((int)$this->classroom_id)) {
            \Flux::toast(variant: 'danger', text: 'Anda tidak memiliki akses untuk menyimpan penilaian ini.');
            return;
        }

        DB::transaction(function () {
            foreach ($this->assessments_data as $studentId => $data) {
                if (empty($data['level'])) continue;

                ExtracurricularAssessment::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'extracurricular_activity_id' => $this->activity_id,
                        'academic_year_id' => $this->academic_year_id,
                        'semester' => $this->semester,
                    ],
                    [
                        'achievement_level' => $data['level'],
                        'description' => $data['description'] ?: null,
                    ]
                );
            }
        });

        \Flux::toast('Penilaian ekstrakurikuler berhasil disimpan.');
    }

    public function with(): array
    {
        $students = [];
        $activities = collect();

        if ($this->classroom_id) {
            $students = User::where('role', 'siswa')
                ->whereHas('profiles', function ($q) {
                    $q->whereHasMorph('profileable', [\App\Models\StudentProfile::class], function ($q) {
                        $q->where('classroom_id', $this->classroom_id);
                    });
                })
                ->orderBy('name')
                ->get();

            $classroom = Classroom::find($this->classroom_id);
            if ($classroom) {
                $activities = ExtracurricularActivity::where('is_active', true)
                    ->where('level_id', $classroom->level_id)
                    ->orderBy('name')
                    ->get();
            }
        }

        return [
            'years' => AcademicYear::orderBy('name', 'desc')->get(),
            'classrooms' => $this->getFilteredClassrooms(),
            'activities' => $activities,
            'students' => $students,
            'selectedActivity' => $this->activity_id ? ExtracurricularActivity::find($this->activity_id) : null,
            'isReadonly' => !$this->canEditAssessments(),
        ];
    }
}
