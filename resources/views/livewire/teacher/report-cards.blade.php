<?php

declare(strict_types=1);

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
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.teacher.layouts.app')] class extends Component {
    // Selection and Form Props
    public $academicYearId = null;
    public $classroomId = null;
    public $semester = '1';
    public $curriculumType = 'conventional';
    public $teacherNotes = '';
    public $characterNotes = '';
    public $selectedStudents = [];

    // UI Props
    public $showPreview = false;
    public $previewData = null;

    public function mount(): void
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

    public function updatedSemester(): void
    {
        $this->loadExistingReports();
    }

    public function generateReportCards(): void
    {
        $teacher = auth()->user();

        try {
            $this->validate([
                'academicYearId' => 'required|exists:academic_years,id',
                'classroomId' => 'required|exists:classrooms,id',
                'semester' => 'required|in:1,2',
                'selectedStudents' => 'required|array|min:1',
            ]);

            // Security check: Verify teacher has access to the classroom
            if (!$teacher->hasAccessToClassroom((int)$this->classroomId)) {
                abort(403, 'Anda tidak memiliki akses ke kelas ini.');
            }

            DB::transaction(function () use ($teacher) {
                foreach ($this->selectedStudents as $studentProfileId) {
                    $studentProfile = StudentProfile::with('profile.user')->find($studentProfileId);
                    
                    // Verify the student belongs to the selected classroom (security)
                    if (!$studentProfile || $studentProfile->classroom_id != $this->classroomId) {
                        continue;
                    }

                    $student = $studentProfile->profile?->user;
                    if (!$student) continue;

                    $aggregatedData = [];
                    $gpa = 0;

                    if ($this->curriculumType === 'merdeka') {
                        // Fetch Competency Assessments
                        $aggregatedData['competencies'] = CompetencyAssessment::where([
                            'student_id' => $student->id,
                            'classroom_id' => $this->classroomId,
                            'academic_year_id' => $this->academicYearId,
                            'semester' => $this->semester,
                        ])->with('subject')->get()->map(fn($c) => [
                            'subject_name' => $c->subject?->name ?? 'N/A',
                            'level' => $c->competency_level,
                            'description' => $c->achievement_description,
                        ])->toArray();

                        // Fetch P5
                        $aggregatedData['p5'] = P5Assessment::where([
                            'student_id' => $student->id,
                            'classroom_id' => $this->classroomId,
                            'academic_year_id' => $this->academicYearId,
                            'semester' => $this->semester,
                        ])->with('p5Project')->get()->map(fn($p) => [
                            'project_name' => $p->p5Project?->name ?? 'N/A',
                            'dimension' => $p->p5Project?->dimension ?? 'N/A',
                            'level' => $p->achievement_level,
                            'description' => $p->description,
                        ])->toArray();

                        // Fetch Extracurricular
                        $aggregatedData['extracurricular'] = ExtracurricularAssessment::where([
                            'student_id' => $student->id,
                            'academic_year_id' => $this->academicYearId,
                            'semester' => $this->semester,
                        ])->with('extracurricularActivity')->get()->map(fn($e) => [
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
                            $aggregatedData['paud'] = $paud->map(fn($p) => [
                                'aspect_name' => $p->developmentalAspect?->name ?? 'N/A',
                                'description' => $p->description,
                            ])->toArray();
                        }
                    } else {
                        // Conventional
                        $scores = Score::where('student_id', $student->id)
                            ->where('classroom_id', (int)$this->classroomId)
                            ->where('academic_year_id', $this->academicYearId)
                            ->with(['subject'])
                            ->get();

                        $scoreItems = [];
                        $totalScore = 0;
                        $scoreCount = 0;

                        foreach ($scores->groupBy('subject_id') as $subjectScores) {
                            $subject = $subjectScores->first()->subject;
                            $subjectTotal = $subjectScores->sum('score');
                            $avgScore = $subjectTotal / $subjectScores->count();
                            
                            $scoreItems[] = [
                                'subject_name' => $subject?->name ?? 'N/A',
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
                            'character_notes' => $this->characterNotes,
                            'status' => 'draft',
                        ]
                    );
                }
            });

            \Flux::toast('Rapor berhasil dibuat untuk ' . count($this->selectedStudents) . ' siswa.');
            $this->reset(['selectedStudents', 'teacherNotes', 'characterNotes']);
            $this->loadExistingReports();
        } catch (\Exception $e) {
            \Flux::toast(variant: 'danger', heading: 'Gagal membuat rapor', text: $e->getMessage());
        }
    }

    public function previewReportCard($reportCardId): void
    {
        $teacher = auth()->user();
        $reportCard = ReportCard::with(['student', 'classroom.level', 'academicYear'])->find($reportCardId);

        if (!$reportCard || !$teacher->hasAccessToClassroom((int)$reportCard->classroom_id)) {
            \Flux::toast(variant: 'danger', text: 'Anda tidak memiliki akses ke rapor ini.');
            return;
        }

        $studentProfile = StudentProfile::where('user_id', $reportCard->student_id)->first();

        $this->previewData = [
            'student' => $reportCard->student,
            'studentProfile' => $studentProfile,
            'reportCard' => $reportCard,
            'classroom' => $reportCard->classroom,
            'academicYear' => $reportCard->academicYear,
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
        $teacher = auth()->user();
        $reportCard = ReportCard::with(['student', 'classroom.level', 'academicYear'])->find($reportCardId);

        if (!$reportCard || !$teacher->hasAccessToClassroom((int)$reportCard->classroom_id)) {
            \Flux::toast(variant: 'danger', text: 'Anda tidak memiliki akses ke rapor ini.');
            return;
        }

        $studentProfile = StudentProfile::where('user_id', $reportCard->student_id)->first();

        $data = [
            'reportCard' => $reportCard,
            'student' => $reportCard->student,
            'studentProfile' => $studentProfile,
            'classroom' => $reportCard->classroom,
            'academicYear' => $reportCard->academicYear,
        ];

        $view = $reportCard->curriculum_type === 'merdeka' ? 'pdf.report-card-merdeka' : 'pdf.report-card';

        try {
            $pdf = Pdf::loadView($view, $data);
            return response()->streamDownload(
                fn () => print($pdf->output()),
                'rapor-' . str($reportCard->student->name)->slug() . '-' . $reportCard->semester . '.pdf',
                ['Content-Type' => 'application/pdf']
            );
        } catch (\Exception $e) {
            \Flux::toast(variant: 'danger', heading: 'PDF Error', text: $e->getMessage());
        }
    }

    public function deleteReportCard($id): void
    {
        $teacher = auth()->user();
        $reportCard = ReportCard::find($id);

        if ($reportCard && $teacher->hasAccessToClassroom((int)$reportCard->classroom_id)) {
            $reportCard->delete();
            $this->loadExistingReports();
            \Flux::toast('Rapor berhasil dihapus.');
        } else {
            \Flux::toast(variant: 'danger', text: 'Anda tidak memiliki izin untuk menghapus rapor ini.');
        }
    }

    public function loadExistingReports(): void
    {
        // Handled in with() to keep it reactive easily
    }

    public function with(): array
    {
        $teacher = auth()->user();
        $assignedClassroomIds = $teacher->getAssignedClassroomIds();

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
            'classrooms' => Classroom::whereIn('id', $assignedClassroomIds)
                ->when($this->academicYearId, fn($q) => $q->where('academic_year_id', $this->academicYearId))
                ->orderBy('name')
                ->get(),
            'students' => $students,
            'existingReports' => $existingReports,
        ];
    }
}; ?>

<div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Generator Rapor') }}</flux:heading>
            <flux:subheading>{{ __('Buat dan kelola rapor siswa berdasarkan data penilaian.') }}</flux:subheading>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Left Column: Generator Form -->
        <div class="lg:col-span-2 space-y-6">
            <div class="p-6 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
                <form wire:submit="generateReportCards" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:select wire:model.live="academicYearId" label="Tahun Ajaran" required>
                            <option value="">Pilih Tahun Ajaran</option>
                            @foreach ($academicYears as $year)
                                <option value="{{ $year->id }}">{{ $year->name }}</option>
                            @endforeach
                        </flux:select>

                        <flux:select wire:model.live="classroomId" label="Kelas" required>
                            <option value="">Pilih Kelas</option>
                            @foreach ($classrooms as $classroom)
                                <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                            @endforeach
                        </flux:select>

                        <flux:select wire:model.live="semester" label="Semester" required>
                            <option value="1">Semester 1 (Ganjil)</option>
                            <option value="2">Semester 2 (Genap)</option>
                        </flux:select>

                        <flux:select wire:model.live="curriculumType" label="Jenis Kurikulum" required>
                            <option value="conventional">Kurikulum 2013 (Konvensional)</option>
                            <option value="merdeka">Kurikulum Merdeka</option>
                        </flux:select>
                    </div>

                    @if (count($students) > 0)
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="block text-sm font-medium">{{ __('Pilih Siswa') }}</label>
                                <div class="flex gap-2 text-xs">
                                    <button type="button" wire:click="$set('selectedStudents', {{ $students->pluck('id') }})" class="text-blue-600 hover:underline">Pilih Semua</button>
                                    <span class="text-zinc-300">|</span>
                                    <button type="button" wire:click="$set('selectedStudents', [])" class="text-zinc-500 hover:underline">Batal</button>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-48 overflow-y-auto border border-zinc-200 dark:border-zinc-700 rounded-lg p-3 bg-zinc-50 dark:bg-zinc-900/50">
                                @foreach ($students as $student)
                                    <label class="flex items-center gap-3 cursor-pointer hover:bg-white dark:hover:bg-zinc-800 p-2 rounded transition-colors border border-transparent hover:border-zinc-200 dark:hover:border-zinc-700">
                                        <input type="checkbox" wire:model="selectedStudents" value="{{ $student->id }}" class="rounded text-blue-600">
                                        <span class="text-sm">{{ $student->profile->user->name ?? 'N/A' }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('selectedStudents') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <div class="grid grid-cols-1 gap-4">
                        @if($curriculumType === 'merdeka')
                            <flux:textarea wire:model="characterNotes" label="Catatan Karakter / Deskripsi P5" rows="2" />
                        @endif
                        <flux:textarea wire:model="teacherNotes" label="Catatan Guru" rows="2" />
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <flux:button type="submit" variant="primary" icon="sparkles" wire:loading.attr="disabled">
                            <span wire:loading.remove>{{ __('Proses & Buat Rapor') }}</span>
                            <span wire:loading>{{ __('Memproses...') }}</span>
                        </flux:button>
                        <flux:button type="button" variant="ghost" wire:click="$refresh" icon="arrow-path">Reset</flux:button>
                    </div>
                </form>
            </div>

            <!-- List of Generated Reports -->
            <div class="p-6 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <flux:heading level="2" size="lg">{{ __('Daftar Rapor Siswa') }}</flux:heading>
                    <flux:badge color="zinc">{{ $existingReports->count() }} Terdata</flux:badge>
                </div>

                @if($existingReports->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left border-collapse">
                            <thead>
                                <tr class="text-zinc-500 border-b border-zinc-100 dark:border-zinc-700">
                                    <th class="py-3 px-2 font-medium">Siswa</th>
                                    <th class="py-3 px-2 font-medium">IPK/Avg</th>
                                    <th class="py-3 px-2 font-medium">Status</th>
                                    <th class="py-3 px-2 font-medium text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                                @foreach($existingReports as $report)
                                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50 transition-colors">
                                        <td class="py-3 px-2 font-medium">{{ $report->student->name }}</td>
                                        <td class="py-3 px-2">
                                            <span class="font-bold text-blue-600">{{ $report->gpa }}</span>
                                        </td>
                                        <td class="py-3 px-2">
                                            <flux:badge size="sm" :color="$report->status === 'final' ? 'green' : 'zinc'">
                                                {{ strtoupper($report->status) }}
                                            </flux:badge>
                                        </td>
                                        <td class="py-3 px-2 text-right space-x-1">
                                            <flux:button variant="ghost" size="sm" icon="eye" wire:click="previewReportCard({{ $report->id }})" />
                                            <flux:button variant="ghost" size="sm" icon="arrow-down-tray" wire:click="exportPdf({{ $report->id }})" />
                                            <button type="button" wire:confirm="Hapus rapor ini?" wire:click="deleteReportCard({{ $report->id }})" class="p-1 text-zinc-400 hover:text-red-500">
                                                <flux:icon icon="trash" class="w-4 h-4" />
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="py-12 flex flex-col items-center justify-center text-zinc-400 border-2 border-dashed border-zinc-100 dark:border-zinc-800 rounded-xl">
                        <flux:icon icon="document-text" class="w-12 h-12 mb-2 opacity-20" />
                        <p>Belum ada rapor yang dibuat untuk kriteria ini.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Right Column: Info & Legend -->
        <div class="space-y-6">
            <div class="p-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-900 rounded-xl">
                <flux:heading size="lg" class="text-blue-900 dark:text-blue-200 mb-2">Panduan Guru</flux:heading>
                <ul class="text-sm text-blue-800 dark:text-blue-300 space-y-2 list-disc pl-4">
                    <li>Pilih parameter akademik (Tahun, Kelas, Semester).</li>
                    <li>Sistem akan menyaring siswa di kelas yang Anda ampu.</li>
                    <li>Centang siswa yang ingin dibuatkan rapornya.</li>
                    <li>Klik <strong>Proses & Buat Rapor</strong> untuk menghitung nilai otomatis.</li>
                    <li>Data diambil dari Penilaian (Nilai), Kompetensi, P5, Ekskul, dan Presensi.</li>
                </ul>
            </div>

            <div class="p-6 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
                <flux:heading size="md" class="mb-4">Informasi Kurikulum</flux:heading>
                <div class="space-y-4">
                    <div>
                        <p class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-1">Kurikulum Merdeka</p>
                        <p class="text-xs text-zinc-600 dark:text-zinc-400">Menggunakan Deskripsi Capaian Kompetensi (BB, MB, BSH, SB) dan Projek P5.</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-1">Konvensional (K13)</p>
                        <p class="text-xs text-zinc-600 dark:text-zinc-400">Menggunakan nilai angka (0-100) dan perhitungan rata-rata otomatis.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    @if ($showPreview && $previewData)
        <flux:modal wire:model="showPreview" class="max-w-4xl">
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <flux:heading size="xl">Pratinjau Rapor</flux:heading>
                    <flux:button variant="ghost" icon="x-mark" wire:click="closePreview" />
                </div>
                
                <div class="p-8 bg-zinc-50 dark:bg-zinc-900 text-black rounded-lg shadow-inner overflow-y-auto max-h-[70vh]">
                    <div class="max-w-3xl mx-auto bg-white p-12 shadow-sm min-h-screen">
                        <div class="text-center border-b-2 border-black pb-4 mb-8">
                            <h1 class="text-2xl font-bold uppercase">RAPOR HASIL BELAJAR</h1>
                            <p class="text-lg font-semibold">{{ $previewData['classroom']->name }}</p>
                            <p>Tahun Ajaran {{ $previewData['academicYear']->name }} - Semester {{ $previewData['reportCard']->semester }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-x-12 gap-y-2 text-sm mb-8">
                            <div class="flex justify-between"><span>Nama Siswa</span> <span>:</span></div>
                            <div class="font-bold">{{ $previewData['student']->name }}</div>
                            <div class="flex justify-between"><span>Nomor Induk / NISN</span> <span>:</span></div>
                            <div>{{ $previewData['studentProfile']->nis ?? '-' }} / {{ $previewData['studentProfile']->nisn ?? '-' }}</div>
                            <div class="flex justify-between"><span>Kelas</span> <span>:</span></div>
                            <div>{{ $previewData['classroom']->name }}</div>
                            <div class="flex justify-between"><span>Tahun Ajaran</span> <span>:</span></div>
                            <div>{{ $previewData['academicYear']->name }}</div>
                        </div>

                        @if($previewData['reportCard']->curriculum_type === 'merdeka')
                            <div class="space-y-6">
                                <!-- Competencies -->
                                <div>
                                    <h3 class="font-bold border-b border-zinc-300 mb-3">A. Capaian Pembelajaran</h3>
                                    <table class="w-full border-collapse border border-black text-sm">
                                        <thead>
                                            <tr class="bg-zinc-100">
                                                <th class="border border-black p-2 text-left">Mata Pelajaran</th>
                                                <th class="border border-black p-2 text-center w-24">Capaian</th>
                                                <th class="border border-black p-2 text-left">Deskripsi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($previewData['reportCard']->scores['competencies'] ?? [] as $comp)
                                                <tr>
                                                    <td class="border border-black p-2 font-medium">{{ $comp['subject_name'] }}</td>
                                                    <td class="border border-black p-2 text-center">{{ $comp['level'] }}</td>
                                                    <td class="border border-black p-2 text-xs italic">{{ $comp['description'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @else
                            <div class="space-y-6">
                                <div>
                                    <h3 class="font-bold border-b border-zinc-300 mb-3">Nilai Mata Pelajaran</h3>
                                    <table class="w-full border-collapse border border-black text-sm">
                                        <thead>
                                            <tr class="bg-zinc-100 text-center">
                                                <th class="border border-black p-2 text-left">Mata Pelajaran</th>
                                                <th class="border border-black p-2 w-32">Nilai Akhir</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($previewData['reportCard']->scores as $score)
                                                <tr>
                                                    <td class="border border-black p-2 font-medium">{{ $score['subject_name'] }}</td>
                                                    <td class="border border-black p-2 text-center font-bold">{{ $score['score'] }}</td>
                                                </tr>
                                            @endforeach
                                            <tr class="bg-zinc-50 font-bold">
                                                <td class="border border-black p-2 text-right uppercase">Rata-rata (IPK)</td>
                                                <td class="border border-black p-2 text-center text-blue-700 text-lg">{{ $previewData['reportCard']->gpa }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        <div class="mt-8 space-y-4">
                            @if($previewData['reportCard']->teacher_notes)
                                <div class="p-3 border border-black italic text-sm">
                                    <strong>Catatan Guru:</strong> {{ $previewData['reportCard']->teacher_notes }}
                                </div>
                            @endif
                        </div>

                        <div class="grid grid-cols-2 gap-12 pt-16 text-sm text-center">
                            <div>
                                <p>Orang Tua/Wali</p>
                                <div class="h-24"></div>
                                <p class="border-b border-black w-48 mx-auto"></p>
                            </div>
                            <div>
                                <p>Guru Kelas</p>
                                <div class="h-24"></div>
                                <p class="font-bold underline">{{ auth()->user()->name }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3">
                    <flux:button variant="ghost" wire:click="closePreview">Tutup</flux:button>
                    <flux:button variant="primary" icon="arrow-down-tray" wire:click="exportPdf({{ $previewData['reportCard']->id }})">Download PDF</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
