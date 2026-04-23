<?php

namespace App\Traits\Assessments;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ReportAttendance;
use App\Models\User;
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

        // Auto select if only one classroom
        $user = auth()->user();
        if ($user->role === 'guru') {
            $assignedIds = $user->getAssignedClassroomIds();
            if (count($assignedIds) === 1) {
                $this->classroom_id = (int) $assignedIds[0];
                $this->loadAttendance();
            }
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
        if (! $this->classroom_id) {
            $this->attendance_data = [];

            return;
        }

        $user = auth()->user();
        if ($user->role === 'guru' && ! $user->hasAccessToClassroom($this->classroom_id)) {
            $this->attendance_data = [];
            $this->dispatch('toast',
                type: 'error',
                title: __('Akses Ditolak'),
                message: __('Anda tidak memiliki akses ke kelas ini.')
            );

            return;
        }

        // Load existing saved report-card version
        $attendances = ReportAttendance::where([
            'classroom_id' => $this->classroom_id,
            'academic_year_id' => $this->academic_year_id,
            'semester' => $this->semester,
        ])->get()->keyBy('student_id');

        // Load daily-logger version as a backup/reference
        $daily = $this->getDailyCounts();

        // Populate state
        $this->attendance_data = [];
        $students = $this->getStudents();

        foreach ($students as $student) {
            if ($attendances->has($student->id)) {
                $att = $attendances->get($student->id);
                $this->attendance_data[$student->id] = [
                    'sick' => $att->sick,
                    'permission' => $att->permission,
                    'absent' => $att->absent,
                ];
            } else {
                // Fallback to daily counts if no final version saved yet
                $d = $daily->get($student->id);
                $this->attendance_data[$student->id] = [
                    'sick' => $d->sick ?? 0,
                    'permission' => $d->permission ?? 0,
                    'absent' => $d->absent ?? 0,
                ];
            }
        }
    }

    public function syncWithDaily(): void
    {
        if (! $this->classroom_id) {
            return;
        }

        $daily = $this->getDailyCounts();
        $students = $this->getStudents();

        foreach ($students as $student) {
            $d = $daily->get($student->id);
            $this->attendance_data[$student->id] = [
                'sick' => $d->sick ?? 0,
                'permission' => $d->permission ?? 0,
                'absent' => $d->absent ?? 0,
            ];
        }

        $this->dispatch('toast',
            type: 'success',
            title: __('Rekap Diperbarui'),
            message: __('Berhasil mengambil rekap dari presensi harian. Jangan lupa menekan "Simpan" untuk memperbarui rapor.')
        );
    }

    protected function getDailyCounts()
    {
        $semesterMonths = $this->semester == '1' ? [7, 8, 9, 10, 11, 12] : [1, 2, 3, 4, 5, 6];

        return DB::table('attendance_items')
            ->join('attendances', 'attendances.id', '=', 'attendance_items.attendance_id')
            ->where('attendances.classroom_id', $this->classroom_id)
            ->where('attendances.academic_year_id', $this->academic_year_id)
            ->where(function ($query) use ($semesterMonths) {
                foreach ($semesterMonths as $month) {
                    $query->orWhereMonth('attendances.date', $month);
                }
            })
            ->groupBy('attendance_items.student_id')
            ->select('attendance_items.student_id')
            ->selectRaw("
                SUM(CASE WHEN status = 's' THEN 1 ELSE 0 END) as sick,
                SUM(CASE WHEN status = 'i' THEN 1 ELSE 0 END) as permission,
                SUM(CASE WHEN status = 'a' THEN 1 ELSE 0 END) as absent
            ")
            ->get()
            ->keyBy('student_id');
    }

    protected function getStudents()
    {
        return User::where('role', 'siswa')
            ->whereHas('profiles', function ($q) {
                $q->whereHasMorph('profileable', [\App\Models\StudentProfile::class], function ($q) {
                    $q->where('classroom_id', $this->classroom_id);
                });
            })
            ->orderBy('name')
            ->get();
    }

    public function save(): void
    {
        if (! $this->classroom_id || ! $this->academic_year_id) {
            return;
        }

        $user = auth()->user();
        if ($user->role === 'guru' && ! $user->hasAccessToClassroom($this->classroom_id)) {
            $this->dispatch('toast',
                type: 'error',
                title: __('Akses Ditolak'),
                message: __('Anda tidak memiliki akses untuk menyimpan presensi ini.')
            );

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
                        'sick' => (int) ($data['sick'] ?? 0),
                        'permission' => (int) ($data['permission'] ?? 0),
                        'absent' => (int) ($data['absent'] ?? 0),
                    ]
                );
            }
        });

        $this->dispatch('toast',
            type: 'success',
            title: __('Data Disimpan'),
            message: __('Data presensi rapor berhasil disimpan.')
        );
    }

    public function with(): array
    {
        $user = auth()->user();

        // Improve classroom filtering to support Admin
        $classrooms = collect();
        if ($user->role === 'guru') {
            $assignedIds = $user->getAssignedClassroomIds();
            $classrooms = Classroom::whereIn('id', $assignedIds)
                ->when($this->academic_year_id, fn ($q) => $q->where('academic_year_id', $this->academic_year_id))
                ->orderBy('name')
                ->get();
        } else {
            // Admin sees all
            $classrooms = Classroom::query()
                ->when($this->academic_year_id, fn ($q) => $q->where('academic_year_id', $this->academic_year_id))
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
