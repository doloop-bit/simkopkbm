<?php

namespace App\Traits\Assessments;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceItem;
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

    /** @var array<string, string> */
    public array $attendance_data = [];

    public function mountHandlesDailyAttendance(): void
    {
        $this->date = now()->format('Y-m-d');
        $activeYear = AcademicYear::where('is_active', true)->first();
        if ($activeYear) {
            $this->academic_year_id = $activeYear->id;
        }

        $classrooms = $this->getAllowedClassrooms();
        if ($classrooms->count() === 1) {
            $this->classroom_id = (int) $classrooms->first()->id;
            $this->loadAttendance();
        }
    }

    public function updatedClassroomId(): void
    {
        $this->loadAttendance();
    }

    public function updatedDate(): void
    {
        $this->loadAttendance();
    }

    public function updatedSubjectId(): void
    {
        $this->loadAttendance();
    }

    public function setStatus($studentId, string $status): void
    {
        $this->attendance_data[(string) $studentId] = $status;
    }

    public function setAllStatus(string $status): void
    {
        foreach ($this->students as $student) {
            $this->setStatus($student->id, $status);
        }
    }

    #[\Livewire\Attributes\Computed]
    public function students()
    {
        if (! $this->classroom_id) {
            return collect();
        }

        return User::where('role', 'siswa')
            ->whereHas('studentProfile', function ($q) {
                $q->where('classroom_id', $this->classroom_id);
            })
            ->orderBy('name')
            ->get();
    }

    public function loadAttendance(): void
    {
        if (! $this->classroom_id || ! $this->date) {
            $this->attendance_data = [];

            return;
        }

        $this->ensureAccessToClassroom((int) $this->classroom_id);

        $attendance = Attendance::where([
            'classroom_id' => $this->classroom_id,
            'subject_id' => $this->subject_id,
            'date' => $this->date,
        ])->with('items')->first();

        $this->notes = $attendance->notes ?? '';
        $existingData = $attendance ? $attendance->items->pluck('status', 'student_id')->toArray() : [];

        $this->attendance_data = [];
        foreach ($this->students as $student) {
            // Force string keys and string values to ensure JSON/PHP consistency in Livewire 4
            $this->attendance_data[(string) $student->id] = (string) ($existingData[$student->id] ?? 'h');
        }
    }

    public function save(): void
    {
        try {
            if (! $this->classroom_id || ! $this->date || ! $this->academic_year_id) {
                $this->dispatch('toast', type: 'error', title: __('Gagal'), message: __('Lengkapi data semester, kelas, dan tanggal.'));

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

                foreach ($this->attendance_data as $studentId => $status) {
                    if (empty($status)) {
                        continue;
                    }

                    AttendanceItem::updateOrCreate(
                        [
                            'attendance_id' => $attendance->id,
                            'student_id' => (int) $studentId,
                        ],
                        [
                            'status' => (string) $status,
                        ]
                    );
                }
            });

            $this->dispatch('attendance-saved');
            $this->dispatch('toast', type: 'success', title: __('Berhasil'), message: __('Presensi berhasil disimpan.'));
        } catch (\Exception $e) {
            $this->dispatch('toast', type: 'error', title: __('Gagal menyimpan'), message: $e->getMessage());
        }
    }

    abstract protected function ensureAccessToClassroom(int $classroomId): void;

    abstract protected function getAllowedClassrooms();

    public function with(): array
    {
        if ($this->classroom_id && empty($this->attendance_data) && $this->students->isNotEmpty()) {
            $this->loadAttendance();
        }

        return [
            'years' => AcademicYear::orderBy('name', 'desc')->get(),
            'classrooms' => $this->getAllowedClassrooms(),
            'subjects' => Subject::orderBy('name')->get(),
        ];
    }
}
