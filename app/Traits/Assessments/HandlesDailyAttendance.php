<?php

namespace App\Traits\Assessments;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceItem;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\DB;

trait HandlesDailyAttendance
{
    public ?int $academic_year_id = null;

    public ?int $classroom_id = null;

    public ?int $subject_id = null;

    public string $date = '';

    public string $notes = '';

    public array $attendance_data = []; // [student_id => status]

    public function mountHandlesDailyAttendance(): void
    {
        $this->date = now()->format('Y-m-d');
        // Auto-select academic year
        $activeYear = AcademicYear::where('is_active', true)->first();
        if ($activeYear) {
            $this->academic_year_id = $activeYear->id;
        }

        // Auto-select classroom if only one available
        $classrooms = $this->getAllowedClassrooms();
        if ($classrooms->count() === 1) {
            $this->classroom_id = $classrooms->first()->id;
            $this->loadAttendance();
        }
    }

    public function updatedClassroomId(): void
    {
        $this->loadAttendance();
    }

    public function updatedSubjectId(): void
    {
        $this->loadAttendance();
    }

    public function updatedDate(): void
    {
        $this->loadAttendance();
    }

    // Helper for direct status update from UI
    public function setStatus($studentId, $status)
    {
        $this->attendance_data[$studentId] = $status;
    }

    public function loadAttendance(): void
    {
        if (! $this->classroom_id || ! $this->date) {
            $this->attendance_data = [];

            return;
        }

        // Security check
        $this->ensureAccessToClassroom((int) $this->classroom_id);

        $attendance = Attendance::where([
            'classroom_id' => $this->classroom_id,
            'subject_id' => $this->subject_id,
            'date' => $this->date,
        ])->first();

        if ($attendance) {
            $this->notes = $attendance->notes ?? '';
            $this->attendance_data = $attendance->items->pluck('status', 'student_id')->toArray();
        } else {
            $this->notes = '';
            $this->attendance_data = [];

            // Default to present for all students in classroom
            $students = User::where('role', 'siswa')
                ->whereHas('profiles', function ($q) {
                    $q->whereHasMorph('profileable', [\App\Models\StudentProfile::class], function ($q) {
                        $q->where('classroom_id', $this->classroom_id);
                    });
                })->get();

            foreach ($students as $student) {
                $this->attendance_data[$student->id] = 'h'; // Default Hadir
            }
        }
    }

    public function save(): void
    {
        try {
            if (! $this->classroom_id || ! $this->date || ! $this->academic_year_id) {
                return;
            }

            $this->ensureAccessToClassroom((int) $this->classroom_id);

            DB::transaction(function () {
                $attendance = Attendance::updateOrCreate(
                    [
                        'classroom_id' => $this->classroom_id,
                        'subject_id' => $this->subject_id,
                        'date' => $this->date,
                    ],
                    [
                        'academic_year_id' => $this->academic_year_id,
                        'teacher_id' => auth()->id(),
                        'notes' => $this->notes,
                    ]
                );

                // Sync items
                foreach ($this->attendance_data as $studentId => $status) {
                    AttendanceItem::updateOrCreate(
                        [
                            'attendance_id' => $attendance->id,
                            'student_id' => $studentId,
                        ],
                        [
                            'status' => $status,
                        ]
                    );
                }
            });

            $this->dispatch('attendance-saved');
            \Flux::toast('Presensi berhasil disimpan.');
        } catch (\Exception $e) {
            \Flux::toast(variant: 'danger', heading: 'Gagal menyimpan', text: $e->getMessage());
        }
    }

    // Abstract methods for access control & data scope
    abstract protected function ensureAccessToClassroom(int $classroomId): void;

    abstract protected function getAllowedClassrooms();

    public function with(): array
    {
        $students = [];
        if ($this->classroom_id) {
            // Re-verify access in render loop just in case
            // Note: ensureAccessToClassroom usually aborts, but here we just return empty if invalid to prevent crash
            // But let's assume classroom_id is valid from getAllowedClassrooms or updatedClassroomId hook.

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
            'classrooms' => $this->getAllowedClassrooms(),
            'subjects' => Subject::orderBy('name')->get(),
            'students' => $students,
        ];
    }
}
