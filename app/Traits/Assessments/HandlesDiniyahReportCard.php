<?php

namespace App\Traits\Assessments;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\DiniyahGrade;
use App\Models\DiniyahReportCard;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;

trait HandlesDiniyahReportCard
{
    use \App\Traits\HasAssessmentLogic;

    public ?int $academic_year_id = null;

    public ?int $classroom_id = null;

    public ?int $student_id = null;

    public string $semester = '1';

    public ?string $teacher_notes = null;

    public function mountHandlesDiniyahReportCard(): void
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if ($activeYear) {
            $this->academic_year_id = $activeYear->id;
        }
    }

    public function updatedClassroomId(): void
    {
        $this->student_id = null;
        $this->teacher_notes = null;
    }

    public function updatedStudentId(): void
    {
        $this->loadReportData();
    }

    public function loadReportData(): void
    {
        if (! $this->student_id || ! $this->academic_year_id || ! $this->semester) {
            $this->teacher_notes = null;

            return;
        }

        $report = DiniyahReportCard::where([
            'student_id' => $this->student_id,
            'academic_year_id' => $this->academic_year_id,
            'semester' => $this->semester,
        ])->first();

        $this->teacher_notes = $report?->teacher_notes;
    }

    public function generate(): void
    {
        if (! $this->student_id || ! $this->classroom_id || ! $this->academic_year_id) {
            $this->dispatch('toast', type: 'error', message: 'Tentukan siswa, kelas, dan tahun ajaran.');

            return;
        }

        // Aggregate scores
        $grades = DiniyahGrade::with('subject')
            ->where([
                'student_id' => $this->student_id,
                'classroom_id' => $this->classroom_id,
                'academic_year_id' => $this->academic_year_id,
                'semester' => $this->semester,
            ])->get();

        if ($grades->isEmpty()) {
            $this->dispatch('toast', type: 'warning', message: 'Belum ada nilai diniyah untuk siswa ini.');

            return;
        }

        $scores = $grades->map(function ($grade) {
            return [
                'subject_id' => $grade->diniyah_subject_id,
                'subject_name' => $grade->subject->name,
                'kkm' => $grade->subject->kkm ?? 70,
                'target' => $grade->subject->target,
                'target_status' => $grade->target_status,
                'has_practice' => $grade->subject->has_practice,
                'knowledge_grade' => $grade->knowledge_grade,
                'practice_grade' => $grade->practice_grade,
                'attitude_grade' => $grade->attitude_grade,
                'achievement' => $grade->achievement,
                'grade' => $grade->grade,
            ];
        })->toArray();

        DiniyahReportCard::updateOrCreate(
            [
                'student_id' => $this->student_id,
                'academic_year_id' => $this->academic_year_id,
                'semester' => $this->semester,
            ],
            [
                'classroom_id' => $this->classroom_id,
                'scores' => $scores,
                'teacher_notes' => $this->teacher_notes,
                'status' => 'final',
            ]
        );

        $this->dispatch('toast', type: 'success', message: 'Rapor diniyah berhasil digenerate.');
    }

    public function downloadPdf(int $reportCardId)
    {
        $reportCard = DiniyahReportCard::with(['student', 'classroom.level', 'academicYear'])->findOrFail($reportCardId);
        $student = $reportCard->student;
        $studentProfile = $student->profiles()->where('profileable_type', \App\Models\StudentProfile::class)->first()?->profileable;
        $classroom = $reportCard->classroom;
        $academicYear = $reportCard->academicYear;

        // Get teacher (wali kelas)
        $teacherAssignment = \App\Models\TeacherAssignment::where([
            'classroom_id' => $classroom->id,
            'academic_year_id' => $academicYear->id,
        ])->whereIn('type', ['class_teacher', 'homeroom'])->first();

        $teacher = $teacherAssignment?->teacher;

        // Fetch attendance
        $attendance = \App\Models\ReportAttendance::where([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'semester' => $reportCard->semester,
        ])->first();

        // Level-specific header info
        $headerInfo = $this->getHeaderInfoForLevel($classroom->level?->education_level);

        $pdf = Pdf::loadView('pdf.diniyah-report-card', [
            'reportCard' => $reportCard,
            'student' => $student,
            'studentProfile' => $studentProfile,
            'classroom' => $classroom,
            'academicYear' => $academicYear,
            'teacher' => $teacher,
            'attendance' => $attendance,
            'headerInfo' => $headerInfo,
            'semester' => $reportCard->semester,
            'terbilang' => fn ($n) => $this->terbilang($n),
        ]);

        $safeStudentName = str_replace(['/', '\\'], '-', $student->name);
        $safeYearName = str_replace(['/', '\\'], '-', $academicYear->name);

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            "Rapor_Diniyah_{$safeStudentName}_{$safeYearName}_Semester_{$reportCard->semester}.pdf"
        );
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

        $existingReports = [];
        if ($this->classroom_id) {
            $existingReports = DiniyahReportCard::where([
                'classroom_id' => $this->classroom_id,
                'academic_year_id' => $this->academic_year_id,
                'semester' => $this->semester,
            ])->get()->keyBy('student_id');
        }

        return [
            'years' => AcademicYear::all(),
            'classrooms' => Classroom::whereHas('level', fn ($q) => $q->whereIn('education_level', ['sd', 'smp', 'sma']))
                ->when(auth()->user()->isGuru(), fn ($q) => $q->whereIn('id', auth()->user()->getAssignedClassroomIds()))
                ->orderBy('name')
                ->get(),
            'students' => $students,
            'existingReports' => $existingReports,
        ];
    }

    private function getHeaderInfoForLevel(?string $educationLevel): array
    {
        $programs = \App\Models\Program::all()->keyBy('level_id');
        $levels = \App\Models\Level::all()->keyBy('education_level');

        if ($educationLevel === 'sd') {
            return [
                'name' => 'MADRASAH DINIYAH AL IHSAN (PAKET A)',
                'address' => 'Jl. Letjen Suprapto No.20 Putatan-Sidomulyo, Ungaran, Kab. Semarang',
                'logo' => $programs->get($levels->get('sd')?->id)?->logo_path,
            ];
        }

        $levelId = ($educationLevel === 'smp') ? $levels->get('smp')?->id : $levels->get('sma')?->id;
        $levelName = ($educationLevel === 'smp') ? 'PAKET B' : 'PAKET C';

        return [
            'name' => "MADRASAH DINIYAH AL IHSAN ({$levelName})",
            'address' => 'Dsn Sitoyo RT 01 RW 03, Ds Keji, Kec. Ungaran Barat, Kab. Semarang',
            'logo' => $programs->get($levelId)?->logo_path,
        ];
    }

    private function terbilang($angka): string
    {
        $angka = abs((float) $angka);
        $baca = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
        $terbilang = '';

        if ($angka < 12) {
            $terbilang = ' '.$baca[$angka];
        } elseif ($angka < 20) {
            $terbilang = $this->terbilang($angka - 10).' belas';
        } elseif ($angka < 100) {
            $terbilang = $this->terbilang($angka / 10).' puluh'.$this->terbilang($angka % 10);
        } elseif ($angka < 200) {
            $terbilang = ' seratus'.$this->terbilang($angka - 100);
        } elseif ($angka < 1000) {
            $terbilang = $this->terbilang($angka / 100).' ratus'.$this->terbilang($angka % 100);
        } elseif ($angka < 2000) {
            $terbilang = ' seribu'.$this->terbilang($angka - 1000);
        } elseif ($angka < 1000000) {
            $terbilang = $this->terbilang($angka / 1000).' ribu'.$this->terbilang($angka % 1000);
        }

        return trim($terbilang);
    }
}
