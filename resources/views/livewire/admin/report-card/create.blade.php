<?php

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ReportCard;
use App\Models\Score;
use App\Models\StudentProfile;
use App\Models\CompetencyAssessment;
use App\Models\P5Assessment;
use App\Models\ExtracurricularAssessment;
use App\Models\ReportAttendance;
use App\Models\DevelopmentalAssessment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use function Livewire\Volt\{state, computed};

new #[Layout('components.admin.layouts.app')] class extends Component {
    use AuthorizesRequests;

    public $academicYearId = null;
    public $classroomId = null;
    public $semester = '1';
    public $curriculumType = 'conventional';
    public $teacherNotes = '';
    public $principalNotes = '';
    public $characterNotes = '';
    public $selectedStudents = [];
    public $showPreview = false;
    public $previewData = null;

    // Data properties
    public $academicYears = [];
    public $classrooms = [];
    public $students = [];

    public function mount(): void
    {
        $this->academicYearId = AcademicYear::latest()->first()?->id;
        $this->loadAcademicYears();
        $this->loadClassrooms();
        $this->loadStudents();
    }

    public function loadAcademicYears(): void
    {
        $this->academicYears = AcademicYear::orderBy('name', 'desc')->get()->toArray();
    }

    public function loadClassrooms(): void
    {
        if ($this->academicYearId) {
            $this->classrooms = Classroom::where('academic_year_id', $this->academicYearId)
                ->with('level')
                ->orderBy('name')
                ->get()
                ->toArray();
        } else {
            $this->classrooms = [];
        }
    }

    public function loadStudents(): void
    {
        if ($this->classroomId) {
            $this->students = StudentProfile::where('classroom_id', $this->classroomId)
                ->with(['profile.user'])
                ->orderBy('created_at')
                ->get()
                ->toArray();
        } else {
            $this->students = [];
        }
    }

    public function updatedAcademicYearId(): void
    {
        $this->classroomId = null;
        $this->selectedStudents = [];
        $this->loadClassrooms();
        $this->loadStudents();
    }

    public function updatedClassroomId(): void
    {
        $this->selectedStudents = [];
        $this->loadStudents();

        // Auto detect curriculum type based on classroom level
        if ($this->classroomId) {
            $classroom = Classroom::with('level')->find($this->classroomId);
            if ($classroom && $classroom->level?->education_level === 'paud') {
                $this->curriculumType = 'merdeka';
            } else {
                $this->curriculumType = 'conventional';
            }
        }
    }

    public function generateReportCards(): void
    {
        $this->validate([
            'academicYearId' => 'required|exists:academic_years,id',
            'classroomId' => 'required|exists:classrooms,id',
            'semester' => 'required|in:1,2',
            'selectedStudents' => 'required|array|min:1',
            'selectedStudents.*' => 'exists:student_profiles,id',
        ]);

        foreach ($this->selectedStudents as $studentProfileId) {
            $studentProfile = StudentProfile::find($studentProfileId);
            $student = $studentProfile->profile?->user;

            if (!$student) {
                continue;
            }

            $aggregatedData = [];
            $gpa = 0;

            if ($this->curriculumType === 'merdeka') {
                // Fetch Competency Assessments
                $competencies = CompetencyAssessment::where([
                    'student_id' => $student->id,
                    'classroom_id' => $this->classroomId,
                    'academic_year_id' => $this->academic_year_id,
                    'semester' => $this->semester,
                ])->with('subject')->get();

                $aggregatedData['competencies'] = $competencies->map(fn($c) => [
                    'subject_name' => $c->subject->name,
                    'level' => $c->competency_level,
                    'description' => $c->achievement_description,
                ])->toArray();

                // Fetch P5 Assessments
                $p5s = P5Assessment::where([
                    'student_id' => $student->id,
                    'classroom_id' => $this->classroomId,
                    'academic_year_id' => $this->academic_year_id,
                    'semester' => $this->semester,
                ])->with('p5Project')->get();

                $aggregatedData['p5'] = $p5s->map(fn($p) => [
                    'project_name' => $p->p5Project->name,
                    'dimension' => $p->p5Project->dimension,
                    'level' => $p->achievement_level,
                    'description' => $p->description,
                ])->toArray();

                // Fetch Extracurricular Assessments
                $ekskuls = ExtracurricularAssessment::where([
                    'student_id' => $student->id,
                    'academic_year_id' => $this->academic_year_id,
                    'semester' => $this->semester,
                ])->with('extracurricularActivity')->get();

                $aggregatedData['extracurricular'] = $ekskuls->map(fn($e) => [
                    'name' => $e->extracurricularActivity->name,
                    'level' => $e->achievement_level,
                    'description' => $e->description,
                ])->toArray();

                // Fetch Attendance Summary
                $attendance = ReportAttendance::where([
                    'student_id' => $student->id,
                    'classroom_id' => $this->classroomId,
                    'academic_year_id' => $this->academic_year_id,
                    'semester' => $this->semester,
                ])->first();

                $aggregatedData['attendance'] = [
                    'sick' => $attendance->sick ?? 0,
                    'permission' => $attendance->permission ?? 0,
                    'absent' => $attendance->absent ?? 0,
                ];

                // Fetch PAUD Developmental if relevant
                $paud = DevelopmentalAssessment::where([
                    'student_id' => $student->id,
                    'classroom_id' => $this->classroomId,
                    'academic_year_id' => $this->academic_year_id,
                    'semester' => $this->semester,
                ])->with('developmentalAspect')->get();

                if ($paud->isNotEmpty()) {
                    $aggregatedData['paud'] = $paud->map(fn($p) => [
                        'aspect_name' => $p->developmentalAspect->name,
                        'aspect_type' => $p->developmentalAspect->aspect_type,
                        'description' => $p->description,
                    ])->toArray();
                }
            } else {
                // Conventional numeric scores logic
                $scores = Score::where('student_id', $student->id)
                    ->where('classroom_id', $this->classroomId)
                    ->where('academic_year_id', $this->academicYearId)
                    ->with(['subject', 'category'])
                    ->get();

                $scoreItems = [];
                $totalScore = 0;
                $scoreCount = 0;

                foreach ($scores->groupBy('subject_id') as $subjectScores) {
                    $subject = $subjectScores->first()->subject;
                    $subjectTotal = 0;
                    $categoryCount = 0;

                    foreach ($subjectScores as $score) {
                        $subjectTotal += $score->score;
                        $categoryCount++;
                    }

                    $avgScore = $categoryCount > 0 ? $subjectTotal / $categoryCount : 0;
                    $scoreItems[$subject->id] = [
                        'subject_name' => $subject->name,
                        'score' => round($avgScore, 2),
                    ];

                    $totalScore += $avgScore;
                    $scoreCount++;
                }

                $aggregatedData = $scoreItems;
                $gpa = $scoreCount > 0 ? round($totalScore / $scoreCount, 2) : 0;
            }

            ReportCard::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'classroom_id' => $this->classroomId,
                    'academic_year_id' => $this->academicYearId,
                    'semester' => $this->semester,
                ],
                [
                    'scores' => $aggregatedData,
                    'gpa' => $gpa,
                    'curriculum_type' => $this->curriculumType,
                    'teacher_notes' => $this->teacherNotes,
                    'principal_notes' => $this->principalNotes,
                    'character_notes' => $this->characterNotes,
                    'status' => 'draft',
                ]
            );
        }

        $this->dispatch('notify', message: 'Rapor berhasil dibuat untuk ' . count($this->selectedStudents) . ' siswa');
        $this->reset(['selectedStudents', 'teacherNotes', 'principalNotes']);
    }

    public function previewReportCard($studentProfileId): void
    {
        $studentProfile = StudentProfile::find($studentProfileId);
        $student = $studentProfile->profile?->user;

        if (!$student) {
            return;
        }

        $reportCard = ReportCard::where('student_id', $student->id)
            ->where('classroom_id', $this->classroomId)
            ->where('academic_year_id', $this->academicYearId)
            ->where('semester', $this->semester)
            ->first();

        if (!$reportCard) {
            $this->dispatch('notify', message: 'Rapor belum dibuat untuk siswa ini');
            return;
        }

        $classroom = Classroom::find($this->classroomId);
        $academicYear = AcademicYear::find($this->academicYearId);

        $this->previewData = [
            'student' => $student,
            'studentProfile' => $studentProfile,
            'reportCard' => $reportCard,
            'classroom' => $classroom,
            'academicYear' => $academicYear,
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
        $reportCard = ReportCard::find($reportCardId);

        if (!$reportCard) {
            $this->dispatch('notify', message: 'Rapor tidak ditemukan');
            return;
        }

        $this->authorize('view', $reportCard);

        $data = [
            'reportCard' => $reportCard,
            'student' => $reportCard->student,
            'studentProfile' => $reportCard->student->studentProfile ?? null,
            'classroom' => $reportCard->classroom,
            'academicYear' => $reportCard->academicYear,
        ];

        $view = 'pdf.report-card';
        if ($reportCard->curriculum_type === 'merdeka') {
            $view = 'pdf.report-card-merdeka';
        }

        $pdf = Pdf::loadView($view, $data);

        return response()->streamDownload(
            fn () => print($pdf->output()),
            'rapor-' . $reportCard->student->name . '-' . $reportCard->semester . '.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }
}; ?>

<div class="space-y-6 p-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ __('Buat Rapor') }}</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('Buat dan kelola rapor siswa') }}</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Form Section -->
        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <form wire:submit="generateReportCards" class="space-y-6">
                    <!-- Academic Year -->
                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">{{ __('Tahun Ajaran') }}</label>
                        <select wire:model.live="academicYearId" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400">
                            <option value="">{{ __('Pilih Tahun Ajaran') }}</option>
                            @foreach ($academicYears as $year)
                                <option value="{{ $year['id'] }}">{{ $year['name'] }}</option>
                            @endforeach
                        </select>
                        @error('academicYearId')
                            <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Classroom -->
                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">{{ __('Kelas') }}</label>
                        <select wire:model.live="classroomId" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400">
                            <option value="">{{ __('Pilih Kelas') }}</option>
                            @foreach ($classrooms as $classroom)
                                <option value="{{ $classroom['id'] }}">{{ $classroom['name'] }}</option>
                            @endforeach
                        </select>
                        @error('classroomId')
                            <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Semester -->
                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">{{ __('Semester') }}</label>
                        <select wire:model="semester" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400">
                            <option value="1">{{ __('Semester 1') }}</option>
                            <option value="2">{{ __('Semester 2') }}</option>
                        </select>
                    </div>

                    <!-- Curriculum Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">{{ __('Kurikulum') }}</label>
                        <select wire:model.live="curriculumType" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400">
                            <option value="conventional">{{ __('Kurikulum 2013 (Konvensional)') }}</option>
                            <option value="merdeka">{{ __('Kurikulum Merdeka') }}</option>
                        </select>
                    </div>

                    <!-- Students Selection -->
                    @if (count($students) > 0)
                        <div>
                            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">{{ __('Pilih Siswa') }}</label>
                            <div class="space-y-2 max-h-64 overflow-y-auto border border-gray-300 rounded-lg p-3 bg-gray-50 dark:bg-gray-700 dark:border-gray-600">
                                @foreach ($students as $student)
                                    <label class="flex items-center gap-3 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 p-2 rounded">
                                        <input type="checkbox" wire:model="selectedStudents" value="{{ $student['id'] }}" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="text-sm text-gray-900 dark:text-white">{{ $student['profile']['user']['name'] ?? 'N/A' }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('selectedStudents')
                                <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span>
                            @enderror
                        </div>
                    @endif

                    @if($curriculumType === 'merdeka')
                    <!-- Character Notes (P5/Character Building) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">{{ __('Catatan Karakter / Deskripsi P5 (Opsional)') }}</label>
                        <textarea wire:model="characterNotes" placeholder="{{ __('Masukkan deskripsi perkembangan karakter siswa') }}" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400" rows="3"></textarea>
                    </div>
                    @endif

                    <!-- Teacher Notes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">{{ __('Catatan Guru') }}</label>
                        <textarea wire:model="teacherNotes" placeholder="{{ __('Masukkan catatan dari guru') }}" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400" rows="3"></textarea>
                    </div>

                    <!-- Principal Notes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">{{ __('Catatan Kepala Sekolah') }}</label>
                        <textarea wire:model="principalNotes" placeholder="{{ __('Masukkan catatan dari kepala sekolah') }}" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400" rows="3"></textarea>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex gap-3">
                        <button type="submit" wire:loading.attr="disabled" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-6 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-50">
                            <span wire:loading.remove>{{ __('Buat Rapor') }}</span>
                            <span wire:loading>{{ __('Memproses...') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Info Section -->
        <div>
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="space-y-4">
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('Informasi') }}</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            {{ __('Pilih tahun ajaran, kelas, dan semester untuk membuat rapor siswa. Nilai akan dihitung otomatis dari data penilaian yang sudah diinput.') }}
                        </p>
                    </div>

                    @if (count($students) > 0)
                        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ __('Total Siswa: ') }} <span class="font-bold">{{ count($students) }}</span>
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ __('Dipilih: ') }} <span class="font-bold">{{ count($selectedStudents) }}</span>
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    @if ($showPreview && $previewData)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closePreview"></div>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('Preview Rapor') }}</h2>
                            <button wire:click="closePreview" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        @if ($previewData)
                            <div class="bg-white dark:bg-gray-900 p-6 rounded-lg space-y-4 max-h-96 overflow-y-auto">
                                <!-- Header -->
                                <div class="text-center border-b border-gray-200 dark:border-gray-700 pb-4">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('RAPOR SISWA') }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $previewData['academicYear']->year }} - {{ __('Semester') }} {{ $semester }}</p>
                                </div>

                                <!-- Student Info -->
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <p class="text-gray-600 dark:text-gray-400">{{ __('Nama Siswa') }}</p>
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $previewData['student']->name }}</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-600 dark:text-gray-400">{{ __('NIS') }}</p>
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $previewData['studentProfile']->nis ?? '-' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-600 dark:text-gray-400">{{ __('Kelas') }}</p>
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $previewData['classroom']->name }}</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-600 dark:text-gray-400">{{ __('IPK') }}</p>
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $previewData['reportCard']->gpa }}</p>
                                    </div>
                                </div>

                                <!-- Content based on Curriculum Type -->
                                @if($previewData['reportCard']->curriculum_type === 'merdeka')
                                    @if(isset($previewData['reportCard']->scores['competencies']))
                                        <div class="space-y-4">
                                            <h4 class="font-bold border-b border-gray-200 dark:border-gray-700 pb-1">{{ __('Capaian Kompetensi') }}</h4>
                                            @foreach($previewData['reportCard']->scores['competencies'] as $comp)
                                                <div class="text-xs">
                                                    <div class="flex justify-between font-semibold">
                                                        <span>{{ $comp['subject_name'] }}</span>
                                                        <span class="px-2 bg-blue-100 text-blue-800 rounded">{{ $comp['level'] }}</span>
                                                    </div>
                                                    <p class="text-gray-600 dark:text-gray-400 mt-1 italic">{{ $comp['description'] }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if(isset($previewData['reportCard']->scores['paud']))
                                        <div class="space-y-4">
                                            <h4 class="font-bold border-b border-gray-200 dark:border-gray-700 pb-1">{{ __('Laporan Perkembangan PAUD') }}</h4>
                                            @foreach($previewData['reportCard']->scores['paud'] as $p)
                                                <div class="text-xs">
                                                    <div class="font-semibold">{{ $p['aspect_name'] }}</div>
                                                    <p class="text-gray-600 dark:text-gray-400 mt-1 italic">{{ $p['description'] }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if(isset($previewData['reportCard']->scores['p5']))
                                        <div class="space-y-4 pt-4">
                                            <h4 class="font-bold border-b border-gray-200 dark:border-gray-700 pb-1">{{ __('Projek P5') }}</h4>
                                            @foreach($previewData['reportCard']->scores['p5'] as $p5)
                                                <div class="text-xs">
                                                    <div class="flex justify-between font-semibold">
                                                        <span>{{ $p5['project_name'] }}</span>
                                                        <span class="px-2 bg-green-100 text-green-800 rounded">{{ $p5['level'] }}</span>
                                                    </div>
                                                    <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $p5['description'] }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                @else
                                    <!-- Scores Table (Conventional) -->
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-sm">
                                            <thead class="bg-gray-100 dark:bg-gray-700">
                                                <tr>
                                                    <th class="px-4 py-2 text-left text-gray-900 dark:text-white">{{ __('Mata Pelajaran') }}</th>
                                                    <th class="px-4 py-2 text-right text-gray-900 dark:text-white">{{ __('Nilai') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                                @foreach ($previewData['reportCard']->scores as $score)
                                                    <tr>
                                                        <td class="px-4 py-2 text-gray-900 dark:text-white">{{ $score['subject_name'] ?? 'N/A' }}</td>
                                                        <td class="px-4 py-2 text-right font-semibold text-gray-900 dark:text-white">{{ $score['score'] ?? '0' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif

                                <!-- Notes -->
                                @if ($previewData['reportCard']->teacher_notes)
                                    <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded border border-blue-200 dark:border-blue-800">
                                        <p class="text-sm font-semibold text-blue-900 dark:text-blue-200">{{ __('Catatan Guru') }}</p>
                                        <p class="text-sm text-blue-800 dark:text-blue-300">{{ $previewData['reportCard']->teacher_notes }}</p>
                                    </div>
                                @endif

                                @if ($previewData['reportCard']->principal_notes)
                                    <div class="bg-green-50 dark:bg-green-900/20 p-3 rounded border border-green-200 dark:border-green-800">
                                        <p class="text-sm font-semibold text-green-900 dark:text-green-200">{{ __('Catatan Kepala Sekolah') }}</p>
                                        <p class="text-sm text-green-800 dark:text-green-300">{{ $previewData['reportCard']->principal_notes }}</p>
                                    </div>
                                @endif
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700 mt-4">
                                <button type="button" wire:click="exportPdf({{ $previewData['reportCard']->id }})" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-6 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                                    {{ __('Export PDF') }}
                                </button>
                                <button type="button" wire:click="closePreview" class="inline-flex items-center justify-center rounded-lg bg-gray-300 px-6 py-2 text-sm font-medium text-gray-900 hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:bg-gray-600 dark:text-white dark:hover:bg-gray-700 dark:focus:ring-offset-gray-800">
                                    {{ __('Tutup') }}
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
