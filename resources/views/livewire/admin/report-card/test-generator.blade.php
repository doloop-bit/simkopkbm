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
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.admin.layouts.app')] class extends Component {
    public $studentId = null;
    public $academicYearId = null;
    public $classroomId = null;
    public $semester = '1';
    public $curriculumType = 'conventional';
    
    public $calcResult = null;
    public $previewData = null;
    public $showPreview = false;
    
    public function mount() {
        $this->academicYearId = AcademicYear::latest()->first()?->id;
    }
    
    public function getStudents() {
        if (!$this->classroomId) return [];
        return StudentProfile::where('classroom_id', $this->classroomId)
            ->with('profile.user')
            ->get();
    }
    
    public function getClassrooms() {
        if (!$this->academicYearId) return [];
        return Classroom::where('academic_year_id', $this->academicYearId)->get();
    }
    
    /**
     * Test 1: Data Calculation
     */
    public function testCalculation() {
        $this->validate([
            'studentId' => 'required',
            'academicYearId' => 'required',
            'classroomId' => 'required',
        ]);
        
        $studentProfile = StudentProfile::find($this->studentId);
        $student = $studentProfile->profile->user;
        
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
                'subject_name' => $c->subject->name,
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
                'project_name' => $p->p5Project->name,
                'dimension' => $p->p5Project->dimension,
                'level' => $p->achievement_level,
                'description' => $p->description,
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
        } else {
            // Conventional Calculation
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
                $subjectTotal = $subjectScores->sum('score');
                $avgScore = $subjectTotal / $subjectScores->count();
                
                $scoreItems[] = [
                    'subject_name' => $subject->name,
                    'score' => round($avgScore, 2),
                ];

                $totalScore += $avgScore;
                $scoreCount++;
            }

            $aggregatedData = $scoreItems;
            $gpa = $scoreCount > 0 ? round($totalScore / $scoreCount, 2) : 0;
        }
        
        $this->calcResult = [
            'aggregatedData' => $aggregatedData,
            'gpa' => $gpa,
            'metadata' => [
                'student_name' => $student->name,
                'classroom' => Classroom::find($this->classroomId)->name,
                'curriculum' => $this->curriculumType
            ]
        ];

        \Flux::toast('Calculation completed.');
    }
    
    /**
     * Test 2: Preview Form
     */
    public function testPreview() {
        $this->testCalculation();
        
        $studentProfile = StudentProfile::find($this->studentId);
        $student = $studentProfile->profile->user;
        
        // Create a temporary ReportCard object for the preview view
        $reportCard = new ReportCard([
            'student_id' => $student->id,
            'classroom_id' => $this->classroomId,
            'academic_year_id' => $this->academicYearId,
            'semester' => $this->semester,
            'curriculum_type' => $this->curriculumType,
            'scores' => $this->calcResult['aggregatedData'],
            'gpa' => $this->calcResult['gpa'],
            'teacher_notes' => 'Ini adalah catatan simulasi guru.',
            'principal_notes' => 'Ini adalah catatan simulasi kepala sekolah.',
            'character_notes' => 'Ini adalah catatan simulasi karakter.',
        ]);
        
        // Ensure relationships are set for the view
        $reportCard->setRelation('student', $student);
        $reportCard->setRelation('classroom', Classroom::find($this->classroomId));
        $reportCard->setRelation('academicYear', AcademicYear::find($this->academicYearId));
        
        $this->previewData = [
            'student' => $student,
            'studentProfile' => $studentProfile,
            'reportCard' => $reportCard,
            'classroom' => Classroom::find($this->classroomId),
            'academicYear' => AcademicYear::find($this->academicYearId),
        ];
        
        $this->showPreview = true;
    }
    
    /**
     * Test 3: PDF Generation
     */
    public function testPdf() {
        $this->testCalculation();
        
        $studentProfile = StudentProfile::find($this->studentId);
        $student = $studentProfile->profile->user;
        
        // Mocking the ReportCard for PDF
        $reportCard = new ReportCard([
            'student_id' => $student->id,
            'classroom_id' => $this->classroomId,
            'academic_year_id' => $this->academicYearId,
            'semester' => $this->semester,
            'curriculum_type' => $this->curriculumType,
            'scores' => $this->calcResult['aggregatedData'],
            'gpa' => $this->calcResult['gpa'],
            'teacher_notes' => 'Test Notes',
            'principal_notes' => 'Test Principal Notes',
            'character_notes' => 'Test Character Notes',
            'status' => 'draft',
        ]);
        
        $reportCard->setRelation('student', $student);
        $reportCard->setRelation('classroom', Classroom::find($this->classroomId));
        $reportCard->setRelation('academicYear', AcademicYear::find($this->academicYearId));

        $data = [
            'reportCard' => $reportCard,
            'student' => $student,
            'studentProfile' => $studentProfile,
            'classroom' => Classroom::find($this->classroomId),
            'academicYear' => AcademicYear::find($this->academicYearId),
        ];

        $view = $this->curriculumType === 'merdeka' ? 'pdf.report-card-merdeka' : 'pdf.report-card';
        
        try {
            $pdf = Pdf::loadView($view, $data);
            return response()->streamDownload(
                fn () => print($pdf->output()),
                'simulasi-rapor-' . str($student->name)->slug() . '.pdf',
                ['Content-Type' => 'application/pdf']
            );
        } catch (\Exception $e) {
            \Flux::toast(variant: 'danger', heading: 'PDF Error', text: $e->getMessage());
        }
    }
}; ?>

<div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Report Card Laboratory') }}</flux:heading>
            <flux:subheading>{{ __('Test individual components of report card generation.') }}</flux:subheading>
        </div>
        <flux:button :href="route('admin.report-card.create')" variant="ghost" icon="document-text">Go to Real Generator</flux:button>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Control Panel -->
        <div class="lg:col-span-1 space-y-4">
            <div class="p-6 bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 space-y-4">
                <flux:heading level="2" size="lg">{{ __('1. Setup Parameters') }}</flux:heading>
                
                <flux:select wire:model.live="academicYearId" label="Academic Year">
                    @foreach(App\Models\AcademicYear::orderBy('name', 'desc')->get() as $year)
                        <option value="{{ $year->id }}">{{ $year->name }}</option>
                    @endforeach
                </flux:select>
                
                <flux:select wire:model.live="classroomId" label="Classroom">
                    <option value="">Select Classroom</option>
                    @foreach($this->getClassrooms() as $room)
                        <option value="{{ $room->id }}">{{ $room->name }}</option>
                    @endforeach
                </flux:select>
                
                <flux:select wire:model.live="studentId" label="Student">
                    <option value="">Select Student</option>
                    @foreach($this->getStudents() as $sp)
                        <option value="{{ $sp->id }}">{{ $sp->profile->user->name }}</option>
                    @endforeach
                </flux:select>
                
                <flux:select wire:model.live="curriculumType" label="Curriculum Type">
                    <option value="conventional">Conventional (K13)</option>
                    <option value="merdeka">Merdeka (PAUD/New)</option>
                </flux:select>

                <flux:select wire:model="semester" label="Semester">
                    <option value="1">Semester 1</option>
                    <option value="2">Semester 2</option>
                </flux:select>
            </div>

            <div class="p-6 bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 space-y-3">
                <flux:heading level="2" size="lg">{{ __('2. Execute Tests') }}</flux:heading>
                
                <flux:button wire:click="testCalculation" variant="primary" icon="calculator" class="w-full justify-start">
                    Test Calculation
                </flux:button>
                
                <flux:button wire:click="testPreview" variant="primary" icon="eye" class="w-full justify-start">
                    Test Preview
                </flux:button>
                
                <flux:button wire:click="testPdf" variant="primary" icon="arrow-down-tray" class="w-full justify-start">
                    Test PDF Generation
                </flux:button>
            </div>
        </div>
        
        <!-- Results Section -->
        <div class="lg:col-span-2 space-y-6">
            <div class="p-6 bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 h-full">
                <div class="flex items-center justify-between mb-4">
                    <flux:heading level="2" size="lg">{{ __('Calculation Debugger') }}</flux:heading>
                    @if($calcResult)
                        <flux:badge color="green" size="sm">Data Loaded</flux:badge>
                    @endif
                </div>

                @if($calcResult)
                    <div class="space-y-4">
                        <div class="grid grid-cols-3 gap-4">
                            <div class="p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                                <p class="text-xs text-zinc-500">Student</p>
                                <p class="font-bold">{{ $calcResult['metadata']['student_name'] }}</p>
                            </div>
                            <div class="p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                                <p class="text-xs text-zinc-500">Classroom</p>
                                <p class="font-bold">{{ $calcResult['metadata']['classroom'] }}</p>
                            </div>
                            <div class="p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                                <p class="text-xs text-zinc-500">GPA / Avg</p>
                                <p class="font-bold text-blue-600">{{ $calcResult['gpa'] }}</p>
                            </div>
                        </div>

                        <div class="relative">
                            <flux:heading size="sm" class="mb-2">Raw Aggregated Data</flux:heading>
                            <div class="bg-zinc-900 text-zinc-300 p-4 rounded-lg font-mono text-xs overflow-auto max-h-[500px]">
                                <pre>{{ json_encode($calcResult['aggregatedData'], JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center h-64 text-zinc-400">
                        <flux:icon icon="beaker" class="w-12 h-12 mb-3 opacity-20" />
                        <p>Configure parameters and click "Test Calculation" to begin.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Enhanced Preview Modal -->
    @if ($showPreview && $previewData)
        <flux:modal wire:model="showPreview" class="max-w-4xl">
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <flux:heading size="xl">Preview Simulation</flux:heading>
                    <flux:button variant="ghost" icon="x-mark" wire:click="$set('showPreview', false)" />
                </div>
                
                <div class="p-8 bg-white text-black rounded-lg shadow-inner border border-zinc-200 overflow-y-auto max-h-[70vh]">
                    <!-- SIMULATED PAPER START -->
                    <div class="max-w-2xl mx-auto space-y-8">
                        <div class="text-center border-b-2 border-black pb-4">
                            <h1 class="text-2xl font-bold uppercase">Laporan Hasil Belajar (Rapor)</h1>
                            <p class="text-lg font-semibold">{{ $previewData['classroom']->name }}</p>
                            <p>Tahun Ajaran {{ $previewData['academicYear']->name }} - Semester {{ $semester }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-x-12 gap-y-2 text-sm">
                            <div class="flex justify-between"><span>Nama Siswa</span> <span>:</span></div>
                            <div class="font-bold">{{ $previewData['student']->name }}</div>
                            
                            <div class="flex justify-between"><span>Nomor Induk / NISN</span> <span>:</span></div>
                            <div>{{ $previewData['studentProfile']->nis ?? '-' }} / {{ $previewData['studentProfile']->nisn ?? '-' }}</div>
                            
                            <div class="flex justify-between"><span>Kelas</span> <span>:</span></div>
                            <div>{{ $previewData['classroom']->name }}</div>
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
                                            @forelse($previewData['reportCard']->scores['competencies'] ?? [] as $comp)
                                                <tr>
                                                    <td class="border border-black p-2 font-medium">{{ $comp['subject_name'] }}</td>
                                                    <td class="border border-black p-2 text-center">{{ $comp['level'] }}</td>
                                                    <td class="border border-black p-2 text-xs italic">{{ $comp['description'] }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="3" class="border border-black p-4 text-center text-zinc-400 italic">No competency data found</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Attendance -->
                                <div>
                                    <h3 class="font-bold border-b border-zinc-300 mb-3">B. Kehadiran</h3>
                                    <table class="w-64 border-collapse border border-black text-sm">
                                        <tbody>
                                            <tr><td class="border border-black p-2 w-32">Sakit</td><td class="border border-black p-2 text-center">{{ $previewData['reportCard']->scores['attendance']['sick'] ?? 0 }} hari</td></tr>
                                            <tr><td class="border border-black p-2">Izin</td><td class="border border-black p-2 text-center">{{ $previewData['reportCard']->scores['attendance']['permission'] ?? 0 }} hari</td></tr>
                                            <tr><td class="border border-black p-2">Alpa</td><td class="border border-black p-2 text-center">{{ $previewData['reportCard']->scores['attendance']['absent'] ?? 0 }} hari</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @else
                            <div class="space-y-6">
                                <div>
                                    <h3 class="font-bold border-b border-zinc-300 mb-3">Nilai Hasil Belajar</h3>
                                    <table class="w-full border-collapse border border-black text-sm">
                                        <thead>
                                            <tr class="bg-zinc-100">
                                                <th class="border border-black p-2 text-left">Mata Pelajaran</th>
                                                <th class="border border-black p-2 text-center w-32">Nilai Akhir</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($previewData['reportCard']->scores as $score)
                                                <tr>
                                                    <td class="border border-black p-2">{{ $score['subject_name'] }}</td>
                                                    <td class="border border-black p-2 text-center font-bold">{{ $score['score'] }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="2" class="border border-black p-4 text-center text-zinc-400 italic">No score data found</td></tr>
                                            @endforelse
                                            <tr class="bg-zinc-50 font-bold">
                                                <td class="border border-black p-2 text-right uppercase">Rata-rata</td>
                                                <td class="border border-black p-2 text-center text-blue-700">{{ $previewData['reportCard']->gpa }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        <div class="grid grid-cols-2 gap-12 pt-12 text-sm">
                            <div class="text-center">
                                <p>Mengetahui,</p>
                                <p>Orang Tua/Wali</p>
                                <div class="h-20"></div>
                                <p class="border-b border-black w-48 mx-auto"></p>
                            </div>
                            <div class="text-center">
                                <p>Malang, {{ now()->translatedFormat('d F Y') }}</p>
                                <p>Guru Kelas</p>
                                <div class="h-20"></div>
                                <p class="font-bold underline">Nama Guru Pengampu</p>
                            </div>
                        </div>
                    </div>
                    <!-- SIMULATED PAPER END -->
                </div>
                
                <div class="flex justify-end gap-3">
                    <flux:button variant="ghost" wire:click="$set('showPreview', false)">Close Preview</flux:button>
                    <flux:button variant="primary" icon="arrow-down-tray" wire:click="testPdf">Download as PDF</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
