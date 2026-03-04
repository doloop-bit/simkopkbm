<?php

namespace App\Traits\Assessments;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\CompetencyAssessment;
use App\Models\DevelopmentalAssessment;
use App\Models\ExtracurricularAssessment;
use App\Models\ReportAttendance;
use App\Models\ReportCard;
use App\Models\StudentProfile;
use App\Models\SubjectGrade;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

trait HandlesReportCardGeneration
{
    // Selection and Form Props
    public $academicYearId = null;

    public $classroomId = null;

    public $semester = '1';

    public $curriculumType = 'merdeka';

    public $teacherNotes = '';

    public $characterNotes = '';

    public $principalNotes = ''; // Admin only usually

    public $selectedStudents = [];

    // UI Props
    public $showPreview = false;

    public $previewData = null;

    // Data Loading Props (Populated by with())
    // Note: In Volt/Livewire, we often pass data via with(), but for state retention we might need properties

    public function mountHandlesReportCardGeneration(): void
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if ($activeYear) {
            $this->academicYearId = $activeYear->id;
        }
        $this->loadExistingReports();
    }

    public function updatedAcademicYearId(): void
    {
        $this->classroomId = null;
        $this->selectedStudents = [];
        $this->loadExistingReports();
    }

    public function updatedClassroomId(): void
    {
        $this->selectedStudents = [];
        $this->loadExistingReports();

        // Auto detect curriculum type (defaulting to merdeka)
        if ($this->classroomId) {
            $this->curriculumType = 'merdeka';
        }
    }

    public function updatedSemester(): void
    {
        $this->loadExistingReports();
    }

    public function generateReportCards(): void
    {
        try {
            $this->validate([
                'academicYearId' => 'required|exists:academic_years,id',
                'classroomId' => 'required|exists:classrooms,id',
                'semester' => 'required|in:1,2',
                'selectedStudents' => 'required|array|min:1',
            ]);

            // Security check hook
            $this->ensureAccessToClassroom((int) $this->classroomId);

            DB::transaction(function () {
                foreach ($this->selectedStudents as $studentProfileId) {
                    $studentProfile = StudentProfile::with('profile.user')->find($studentProfileId);

                    // Verify the student belongs to the selected classroom (security)
                    if (! $studentProfile || $studentProfile->classroom_id != $this->classroomId) {
                        continue;
                    }

                    $student = $studentProfile->profile?->user;
                    if (! $student) {
                        continue;
                    }

                    $aggregatedData = [];
                    $gpa = 0;

                    // Kurikulum Merdeka logic only
                    // Fetch Subject Grades (New Grading System)
                    $aggregatedData['subject_grades'] = SubjectGrade::where([
                        'student_id' => $student->id,
                        'classroom_id' => $this->classroomId,
                        'academic_year_id' => $this->academicYearId,
                        'semester' => $this->semester,
                    ])->with('subject')->get()->map(function ($g) {
                        return [
                            'subject_name' => $g->subject?->name ?? 'N/A',
                            'grade' => $g->grade,
                            'best_tp' => $g->getBestTpsAttribute()->pluck('description')->toArray(),
                            'improvement_tp' => $g->getImprovementTpsAttribute()->pluck('description')->toArray(),
                        ];
                    })->toArray();

                    // Fetch Competency Assessments (if needed)
                    $aggregatedData['competencies'] = CompetencyAssessment::where([
                        'student_id' => $student->id,
                        'classroom_id' => $this->classroomId,
                        'academic_year_id' => $this->academicYearId,
                        'semester' => $this->semester,
                    ])->with('subject')->get()->map(fn ($c) => [
                        'subject_name' => $c->subject?->name ?? 'N/A',
                        'level' => $c->competency_level,
                        'description' => $c->achievement_description,
                    ])->toArray();

                    // Fetch Extracurricular
                    $aggregatedData['extracurricular'] = ExtracurricularAssessment::where([
                        'student_id' => $student->id,
                        'academic_year_id' => $this->academicYearId,
                        'semester' => $this->semester,
                    ])->with('extracurricularActivity')->get()->map(fn ($e) => [
                        'name' => $e->extracurricularActivity?->name ?? 'N/A',
                        'level' => $e->achievement_level,
                        'description' => $e->description,
                    ])->toArray();

                    // Fetch Attendance
                    $attendance = ReportAttendance::where([
                        'student_id' => $student->id,
                        'classroom_id' => $this->classroomId,
                        'academic_year_id' => $this->academicYearId,
                        'semester' => $this->semester,
                    ])->first();

                    $aggregatedData['attendance'] = [
                        'sick' => $attendance->sick ?? 0,
                        'permission' => $attendance->permission ?? 0,
                        'absent' => $attendance->absent ?? 0,
                    ];

                    // Fetch PAUD
                    $paud = DevelopmentalAssessment::where([
                        'student_id' => $student->id,
                        'classroom_id' => $this->classroomId,
                        'academic_year_id' => $this->academicYearId,
                        'semester' => $this->semester,
                    ])->with('developmentalAspect')->get();

                    if ($paud->isNotEmpty()) {
                        $aggregatedData['paud'] = $paud->map(fn ($p) => [
                            'aspect_name' => $p->developmentalAspect?->name ?? 'N/A',
                            'description' => $p->description,
                        ])->toArray();
                    }

                    $dataToUpdate = [
                        'scores' => $aggregatedData,
                        'gpa' => $gpa,
                        'curriculum_type' => $this->curriculumType,
                        'teacher_notes' => $this->teacherNotes,
                        'character_notes' => $this->characterNotes,
                        'status' => 'draft',
                    ];

                    // Add principal notes if property exists and is set (Admin usually)
                    if (property_exists($this, 'principalNotes')) {
                        $dataToUpdate['principal_notes'] = $this->principalNotes;
                    }

                    ReportCard::updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'classroom_id' => $this->classroomId,
                            'academic_year_id' => $this->academicYearId,
                            'semester' => $this->semester,
                        ],
                        $dataToUpdate
                    );
                }
            });

            \Flux::toast('Rapor berhasil dibuat untuk '.count($this->selectedStudents).' siswa.');
            $this->reset(['selectedStudents', 'teacherNotes', 'characterNotes']);
            if (property_exists($this, 'principalNotes')) {
                $this->reset(['principalNotes']);
            }
            $this->loadExistingReports();
        } catch (\Exception $e) {
            \Flux::toast(variant: 'danger', heading: 'Gagal membuat rapor', text: $e->getMessage());
        }
    }

    public function previewReportCard($reportCardId): void
    {
        $reportCard = ReportCard::with(['student', 'classroom.level', 'academicYear'])->find($reportCardId);

        if (! $reportCard) {
            \Flux::toast(variant: 'danger', text: 'Rapor tidak ditemukan.');

            return;
        }

        $this->ensureAccessToClassroom((int) $reportCard->classroom_id);

        $studentProfile = StudentProfile::whereHas('profile', function ($q) use ($reportCard) {
            $q->where('user_id', $reportCard->student_id);
        })->first();

        $this->previewData = [
            'student' => $reportCard->student,
            'studentProfile' => $studentProfile,
            'reportCard' => $reportCard,
            'classroom' => $reportCard->classroom,
            'academicYear' => $reportCard->academicYear,
            'teacher' => $this->getTeacherForPreview($reportCard),
        ];

        $this->showPreview = true;
    }

    public function closePreview(): void
    {
        $this->showPreview = false;
        $this->previewData = null;
    }

    public function exportPdf($reportCardId)
    {
        $reportCard = ReportCard::with(['student', 'classroom.level', 'academicYear'])->find($reportCardId);

        if (! $reportCard) {
            \Flux::toast(variant: 'danger', text: 'Rapor tidak ditemukan.');

            return;
        }

        $this->ensureAccessToClassroom((int) $reportCard->classroom_id);

        $studentProfile = StudentProfile::whereHas('profile', function ($q) use ($reportCard) {
            $q->where('user_id', $reportCard->student_id);
        })->first();

        $data = [
            'reportCard' => $reportCard,
            'student' => $reportCard->student,
            'studentProfile' => $studentProfile,
            'classroom' => $reportCard->classroom,
            'academicYear' => $reportCard->academicYear,
            'teacher' => $this->getTeacherForPreview($reportCard),
        ];

        $view = 'pdf.report-card-merdeka';

        try {
            $pdf = Pdf::loadView($view, $data);

            return response()->streamDownload(
                fn () => print ($pdf->output()),
                'rapor-'.str($reportCard->student->name)->slug().'-'.$reportCard->semester.'.pdf',
                ['Content-Type' => 'application/pdf']
            );
        } catch (\Exception $e) {
            \Flux::toast(variant: 'danger', heading: 'PDF Error', text: $e->getMessage());
        }
    }

    public function deleteReportCard($id): void
    {
        $reportCard = ReportCard::find($id);

        if ($reportCard) {
            $this->ensureAccessToClassroom((int) $reportCard->classroom_id);
            $reportCard->delete();
            $this->loadExistingReports();
            \Flux::toast('Rapor berhasil dihapus.');
        } else {
            \Flux::toast(variant: 'danger', text: 'Rapor tidak ditemukan.');
        }
    }

    public function loadExistingReports(): void
    {
        // Handled reactively
    }

    // Abstract methods to implement in component
    abstract protected function ensureAccessToClassroom(int $classroomId): void;

    // Helper to get allowed classrooms for dropdown
    abstract protected function getAllowedClassrooms();

    // Helper to get teacher name for preview (differs for admin/teacher view)
    protected function getTeacherForPreview($reportCard)
    {
        return auth()->user(); // Default to current user, override in Admin if needed
    }

    public function with(): array
    {
        $existingReports = collect();
        $students = [];

        if ($this->classroomId && $this->academicYearId) {
            $existingReports = ReportCard::where([
                'classroom_id' => $this->classroomId,
                'academic_year_id' => $this->academicYearId,
                'semester' => $this->semester,
            ])->with('student')->get();

            $students = StudentProfile::where('classroom_id', $this->classroomId)
                ->with(['profile.user'])
                ->orderBy('created_at')
                ->get();
        }

        return [
            'academicYears' => AcademicYear::orderBy('name', 'desc')->get(),
            'classrooms' => $this->getAllowedClassrooms(),
            'students' => $students,
            'existingReports' => $existingReports,
        ];
    }
}
