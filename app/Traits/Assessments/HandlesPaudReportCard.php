<?php

namespace App\Traits\Assessments;

use App\Models\AcademicYear;
use App\Models\PaudCpElement;
use App\Models\PaudReportCard;
use App\Models\PaudSklItem;
use App\Models\PaudTp;
use App\Models\ReportAttendance;
use App\Models\SchoolProfile;
use App\Models\StudentPeriodicRecord;
use App\Models\StudentProfile;
use App\Models\TeacherAssignment;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait HandlesPaudReportCard
{
    public ?int $academic_year_id = null;

    public ?int $classroom_id = null;

    public string $semester = '1';

    public string $display_mode = 'cp'; // 'cp' or 'skl'

    public array $selected_students = [];

    public ?int $active_student_id = null;

    /** @var array<int, string> */
    public array $teacher_notes = []; // [student_id => narrative text]

    /** @var array<int, array<int, string>> */
    public array $cp_summaries = []; // [student_id => [cp_element_id => narrative text]]

    public bool $showPreview = false;

    public ?int $previewReportId = null;

    public function mountHandlesPaudReportCard(): void
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if ($activeYear) {
            $this->academic_year_id = $activeYear->id;
        }
    }

    public function updatedAcademicYearId(): void
    {
        $this->classroom_id = null;
        $this->selected_students = [];
        $this->cp_summaries = [];
        $this->teacher_notes = [];
        $this->active_student_id = null;
        $this->resetValidation();
    }

    public function updatedClassroomId(): void
    {
        $this->selected_students = [];
        $this->cp_summaries = [];
        $this->teacher_notes = [];
        $this->active_student_id = null;
    }

    public function updatedSemester(): void
    {
        $this->selected_students = [];
        $this->cp_summaries = [];
        $this->teacher_notes = [];
        $this->active_student_id = null;
    }

    public function selectActiveStudent(int $studentId): void
    {
        $this->active_student_id = $studentId;

        // Ensure this student is in selected_students
        if (! in_array($studentId, $this->selected_students)) {
            $this->selected_students[] = $studentId;
        }

        $this->loadStudentNotes($studentId);
    }

    protected function loadStudentNotes(int $studentId): void
    {
        if (! isset($this->cp_summaries[$studentId])) {
            $report = PaudReportCard::where([
                'student_id' => $studentId,
                'classroom_id' => $this->classroom_id,
                'academic_year_id' => $this->academic_year_id,
                'semester' => $this->semester,
            ])->first();

            if ($report) {
                $this->cp_summaries[$studentId] = $report->cp_summaries ?? [];
                $this->teacher_notes[$studentId] = $report->teacher_notes ?? '';
            } else {
                $this->cp_summaries[$studentId] = [];
                $this->teacher_notes[$studentId] = '';
            }
        }
    }

    public function updatedSelectedStudents(): void
    {
        foreach ($this->selected_students as $studentId) {
            $this->loadStudentNotes((int) $studentId);
        }

        // Set active student if none selected
        if ($this->active_student_id && ! in_array($this->active_student_id, $this->selected_students)) {
            $this->active_student_id = count($this->selected_students) > 0 ? (int) $this->selected_students[0] : null;
        } elseif (! $this->active_student_id && count($this->selected_students) > 0) {
            $this->active_student_id = (int) $this->selected_students[0];
        }
    }

    public function generateReports(): void
    {
        $this->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'semester' => 'required|in:1,2',
            'selected_students' => 'required|array|min:1',
            'display_mode' => 'required|in:cp,skl',
        ]);

        DB::transaction(function () {
            foreach ($this->selected_students as $studentId) {
                // Load attendance
                $attendance = ReportAttendance::where([
                    'student_id' => $studentId,
                    'academic_year_id' => $this->academic_year_id,
                    'semester' => $this->semester,
                ])->first();

                // Load physical data - through student profile
                $studentProfileId = \App\Models\Profile::where('user_id', $studentId)
                    ->where('profileable_type', \App\Models\StudentProfile::class)
                    ->first()?->profileable_id;

                $periodicRecord = $studentProfileId ? StudentPeriodicRecord::where([
                    'student_profile_id' => $studentProfileId,
                    'academic_year_id' => $this->academic_year_id,
                    'semester' => (int) $this->semester,
                ])->latest()->first() : null;

                // Generate access token if publishing
                $accessToken = Str::random(48);

                PaudReportCard::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'classroom_id' => $this->classroom_id,
                        'academic_year_id' => $this->academic_year_id,
                        'semester' => $this->semester,
                    ],
                    [
                        'cp_summaries' => $this->cp_summaries[$studentId] ?? [],
                        'display_mode' => $this->display_mode,
                        'teacher_notes' => $this->teacher_notes[$studentId] ?? '',
                        'attendance' => $attendance ? [
                            'sick' => $attendance->sick ?? 0,
                            'permission' => $attendance->permission ?? 0,
                            'absent' => $attendance->absent ?? 0,
                        ] : ['sick' => 0, 'permission' => 0, 'absent' => 0],
                        'physical_data' => $periodicRecord ? [
                            'weight' => $periodicRecord->weight,
                            'height' => $periodicRecord->height,
                        ] : ['weight' => null, 'height' => null],
                        'access_token' => $accessToken,
                        'status' => 'draft',
                    ]
                );
            }
        });

        $this->selected_students = [];
        session()->flash('success', __('Rapor PAUD berhasil digenerate untuk '.count($this->selected_students).' siswa.'));
    }

    public function publishReport(int $reportId): void
    {
        $report = PaudReportCard::findOrFail($reportId);
        $report->update([
            'status' => 'published',
            'access_token' => $report->access_token ?? Str::random(48),
        ]);

        session()->flash('success', __('Rapor berhasil dipublikasikan. Orang tua dapat mengakses via link.'));
    }

    public function unpublishReport(int $reportId): void
    {
        $report = PaudReportCard::findOrFail($reportId);
        $report->update(['status' => 'draft']);

        session()->flash('success', __('Rapor dikembalikan ke draft.'));
    }

    public function deleteReport(int $reportId): void
    {
        PaudReportCard::findOrFail($reportId)->delete();
        session()->flash('success', __('Rapor berhasil dihapus.'));
    }

    public function previewReport(int $reportId): void
    {
        $this->previewReportId = $reportId;
        $this->showPreview = true;
    }

    public function closePreview(): void
    {
        $this->showPreview = false;
        $this->previewReportId = null;
    }

    public function downloadPdf(int $reportId)
    {
        $report = PaudReportCard::with([
            'student',
            'student.profiles.profileable',
            'classroom.level',
            'academicYear',
        ])->findOrFail($reportId);

        $student = $report->student;
        $studentProfile = $student->profiles()->where('profileable_type', StudentProfile::class)->first()?->profileable;
        $classroom = $report->classroom;
        $academicYear = $report->academicYear;

        // Get teacher (wali kelas)
        $teacherAssignment = TeacherAssignment::where([
            'classroom_id' => $classroom->id,
            'academic_year_id' => $academicYear->id,
        ])->whereIn('type', ['class_teacher', 'homeroom'])->first();
        $teacher = $teacherAssignment?->teacher;

        // Get assessment data
        $tps = PaudTp::where([
            'classroom_id' => $classroom->id,
            'academic_year_id' => $academicYear->id,
            'semester' => $report->semester,
        ])->with(['cpElement', 'sklItem', 'assessments' => fn ($q) => $q->where('student_id', $student->id)])
            ->orderBy('order')
            ->get();

        $cpElements = PaudCpElement::orderBy('order')->get();
        $sklItems = PaudSklItem::orderBy('order')->get();
        $schoolProfile = SchoolProfile::active();

        $pdf = Pdf::loadView('pdf.paud-report-card', [
            'reportCard' => $report,
            'student' => $student,
            'studentProfile' => $studentProfile,
            'classroom' => $classroom,
            'academicYear' => $academicYear,
            'teacher' => $teacher,
            'tps' => $tps,
            'cpElements' => $cpElements,
            'sklItems' => $sklItems,
            'schoolProfile' => $schoolProfile,
        ])->setPaper('a4', 'portrait');

        $safeStudentName = str_replace(['/', '\\'], '-', $student->name);
        $safeYearName = str_replace(['/', '\\'], '-', $academicYear->name);

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            "Rapor_PAUD_{$safeStudentName}_{$safeYearName}_Semester_{$report->semester}.pdf"
        );
    }

    protected function getStudentsInClassroom(): \Illuminate\Database\Eloquent\Collection
    {
        if (! $this->classroom_id) {
            return new \Illuminate\Database\Eloquent\Collection;
        }

        return User::where('role', 'siswa')
            ->whereHas('profiles.profileable', fn ($q) => $q->where('classroom_id', $this->classroom_id))
            ->orderBy('name')
            ->get();
    }

    public function getActiveStudentAssessments(): array
    {
        if (! $this->active_student_id || ! $this->classroom_id || ! $this->academic_year_id) {
            return [];
        }

        return PaudTp::where([
            'classroom_id' => $this->classroom_id,
            'academic_year_id' => $this->academic_year_id,
            'semester' => $this->semester,
        ])
            ->with(['cpElement', 'sklItem', 'assessments' => fn ($q) => $q->where('student_id', $this->active_student_id)])
            ->orderBy('order')
            ->get()
            ->toArray();
    }

    public function generateSingleReport(int $studentId): void
    {
        $this->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'semester' => 'required|in:1,2',
            'display_mode' => 'required|in:cp,skl',
        ]);

        DB::transaction(function () use ($studentId) {
            $attendance = ReportAttendance::where([
                'student_id' => $studentId,
                'academic_year_id' => $this->academic_year_id,
                'semester' => $this->semester,
            ])->first();

            $studentProfileId = \App\Models\Profile::where('user_id', $studentId)
                ->where('profileable_type', \App\Models\StudentProfile::class)
                ->first()?->profileable_id;

            $periodicRecord = $studentProfileId ? StudentPeriodicRecord::where([
                'student_profile_id' => $studentProfileId,
                'academic_year_id' => $this->academic_year_id,
                'semester' => (int) $this->semester,
            ])->latest()->first() : null;

            $accessToken = Str::random(48);

            PaudReportCard::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'classroom_id' => $this->classroom_id,
                    'academic_year_id' => $this->academic_year_id,
                    'semester' => $this->semester,
                ],
                [
                    'cp_summaries' => $this->cp_summaries[$studentId] ?? [],
                    'display_mode' => $this->display_mode,
                    'teacher_notes' => $this->teacher_notes[$studentId] ?? '',
                    'attendance' => $attendance ? [
                        'sick' => $attendance->sick ?? 0,
                        'permission' => $attendance->permission ?? 0,
                        'absent' => $attendance->absent ?? 0,
                    ] : ['sick' => 0, 'permission' => 0, 'absent' => 0],
                    'physical_data' => $periodicRecord ? [
                        'weight' => $periodicRecord->weight,
                        'height' => $periodicRecord->height,
                    ] : ['weight' => null, 'height' => null],
                    'access_token' => $accessToken,
                    'status' => 'draft',
                ]
            );
        });

        session()->flash('success', __('Rapor PAUD berhasil disimpan.'));
    }

    protected function getExistingReports(): \Illuminate\Database\Eloquent\Collection
    {
        if (! $this->classroom_id || ! $this->academic_year_id) {
            return new \Illuminate\Database\Eloquent\Collection;
        }

        return PaudReportCard::with('student')
            ->where([
                'classroom_id' => $this->classroom_id,
                'academic_year_id' => $this->academic_year_id,
                'semester' => $this->semester,
            ])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
