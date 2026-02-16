<?php

namespace App\Traits\Assessments;

use App\Models\User;
use App\Models\Classroom;
use App\Models\AcademicYear;
use App\Models\ReportAttendance;
use Illuminate\Support\Facades\DB;

trait HandlesAttendanceAssessment
{
    // Need HasAssessmentLogic for canEditAssessments() ?? or implement local.
    // The original attendance component didn't use HasAssessmentLogic, it used local auth checks.
    // But consistent layout needs getLayout? No, we use explicit Layout.
    // But shared logic might benefit from getFilteredClassrooms?
    // The original used $teacher->getAssignedClassroomIds().
    // We'll keep logic close to original but organized.
    
    public ?int $academic_year_id = null;
    public ?int $classroom_id = null;
    public string $semester = '1';

    public array $attendance_data = []; // [student_id => ['sick' => 0, 'permission' => 0, 'absent' => 0]]

    public function mountHandlesAttendanceAssessment(): void
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if ($activeYear) {
            $this->academic_year_id = $activeYear->id;
        }
    }

    public function updatedClassroomId(): void
    {
        $this->loadAttendance();
    }

    public function updatedSemester(): void
    {
        $this->loadAttendance();
    }

    public function loadAttendance(): void
    {
        if (!$this->classroom_id) {
            $this->attendance_data = [];
            return;
        }

        // Verify teacher has access
        // Ideally use HasAssessmentLogic for consistency if possible, but let's stick to working logic.
        $user = auth()->user();
        if ($user->role === 'guru' && !$user->hasAccessToClassroom($this->classroom_id)) {
            $this->attendance_data = [];
            \Flux::toast(variant: 'danger', text: 'Anda tidak memiliki akses ke kelas ini.');
            return;
        }

        // Load existing attendance summaries
        $attendances = ReportAttendance::where([
            'classroom_id' => $this->classroom_id,
            'academic_year_id' => $this->academic_year_id,
            'semester' => $this->semester,
        ])->get();

        $this->attendance_data = $attendances->mapWithKeys(function ($att) {
            return [
                $att->student_id => [
                    'sick' => $att->sick,
                    'permission' => $att->permission,
                    'absent' => $att->absent,
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
            if (!isset($this->attendance_data[$student->id])) {
                $this->attendance_data[$student->id] = [
                    'sick' => 0,
                    'permission' => 0,
                    'absent' => 0,
                ];
            }
        }
    }

    public function save(): void
    {
        if (!$this->classroom_id || !$this->academic_year_id) {
            return;
        }

        // Verify teacher has access
        $user = auth()->user();
        if ($user->role === 'guru' && !$user->hasAccessToClassroom($this->classroom_id)) {
            \Flux::toast(variant: 'danger', text: 'Anda tidak memiliki akses untuk menyimpan presensi ini.');
            return;
        }

        DB::transaction(function () {
            foreach ($this->attendance_data as $studentId => $data) {
                ReportAttendance::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'academic_year_id' => $this->academic_year_id,
                        'semester' => $this->semester,
                    ],
                    [
                        'classroom_id' => $this->classroom_id,
                        'sick' => (int)($data['sick'] ?? 0),
                        'permission' => (int)($data['permission'] ?? 0),
                        'absent' => (int)($data['absent'] ?? 0),
                    ]
                );
            }
        });

        \Flux::toast('Data presensi rapor berhasil disimpan.');
    }

    public function with(): array
    {
        $user = auth()->user();
        
        // Improve classroom filtering to support Admin
        $classrooms = collect();
        if ($user->role === 'guru') {
            $assignedIds = $user->getAssignedClassroomIds();
            $classrooms = Classroom::whereIn('id', $assignedIds)
                ->when($this->academic_year_id, fn($q) => $q->where('academic_year_id', $this->academic_year_id))
                ->orderBy('name')
                ->get();
        } else {
            // Admin sees all
            $classrooms = Classroom::query()
                ->when($this->academic_year_id, fn($q) => $q->where('academic_year_id', $this->academic_year_id))
                ->orderBy('name')
                ->get();
        }

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
            'years' => AcademicYear::orderBy('name', 'desc')->get(),
            'classrooms' => $classrooms,
            'students' => $students,
        ];
    }
}
