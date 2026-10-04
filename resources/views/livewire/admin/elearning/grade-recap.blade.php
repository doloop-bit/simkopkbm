<?php

declare(strict_types=1);

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\OnlineExam;
use App\Models\OnlineExamSubmission;
use App\Models\Subject;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public ?int $filterAcademicYear = null;

    public string $filterSemester = '';

    public ?int $filterClassroom = null;

    public ?int $filterSubject = null;

    public ?int $filterExam = null;

    public string $filterStatus = '';

    public function updatedFilterAcademicYear(): void
    {
        $this->filterClassroom = null;
        $this->filterExam = null;
        $this->resetPage();
    }

    public function updatedFilterSemester(): void
    {
        $this->filterExam = null;
        $this->resetPage();
    }

    public function updatedFilterClassroom(): void
    {
        $this->filterExam = null;
        $this->resetPage();
    }

    public function updatedFilterSubject(): void
    {
        $this->filterExam = null;
        $this->resetPage();
    }

    public function updatedFilterExam(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        $selectedYearId = $this->filterAcademicYear ?? $activeYear?->id;

        $submissionsQuery = OnlineExamSubmission::query()
            ->with(['student', 'exam.subject', 'exam.classroom', 'exam.academicYear'])
            ->whereHas('exam', function ($q) use ($selectedYearId) {
                if ($selectedYearId) {
                    $q->where('academic_year_id', $selectedYearId);
                }
                if ($this->filterSemester) {
                    $q->where('semester', $this->filterSemester);
                }
                if ($this->filterClassroom) {
                    $q->where('classroom_id', $this->filterClassroom);
                }
                if ($this->filterSubject) {
                    $q->where('subject_id', $this->filterSubject);
                }
                if ($this->filterExam) {
                    $q->where('id', $this->filterExam);
                }
            })
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->latest('submitted_at');

        // Stats
        $totalSubmissions = (clone $submissionsQuery)->count();
        $gradedCount = (clone $submissionsQuery)->where('status', 'graded')->count();
        $avgScore = (clone $submissionsQuery)->where('status', 'graded')->avg('total_score');
        $passCount = (clone $submissionsQuery)->where('status', 'graded')
            ->whereHas('exam', fn ($q) => $q->whereColumn('online_exam_submissions.total_score', '>=', 'online_exams.passing_grade'))
            ->count();

        // Exams dropdown (filtered by academic year, semester, classroom, subject)
        $examsQuery = OnlineExam::query()
            ->when($selectedYearId, fn ($q) => $q->where('academic_year_id', $selectedYearId))
            ->when($this->filterSemester, fn ($q) => $q->where('semester', $this->filterSemester))
            ->when($this->filterClassroom, fn ($q) => $q->where('classroom_id', $this->filterClassroom))
            ->when($this->filterSubject, fn ($q) => $q->where('subject_id', $this->filterSubject))
            ->orderBy('title');

        $academicYears = AcademicYear::orderByDesc('name')->get()->map(fn ($y) => [
            'id' => $y->id,
            'name' => $y->name.($y->is_active ? ' ('.__('Aktif').')' : ''),
        ]);

        return [
            'submissions' => $submissionsQuery->paginate(15),
            'academicYears' => $academicYears,
            'classrooms' => Classroom::when($selectedYearId, fn ($q) => $q->where('academic_year_id', $selectedYearId))
                ->with('level')
                ->orderBy('name')
                ->get()
                ->map(fn ($c) => ['id' => $c->id, 'name' => trim(($c->level?->name ?? '').' - '.$c->name, ' -')]),
            'subjects' => Subject::orderBy('name')->get()->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]),
            'exams' => $examsQuery->get()->map(fn ($e) => ['id' => $e->id, 'name' => $e->title]),
            'totalSubmissions' => $totalSubmissions,
            'gradedCount' => $gradedCount,
            'avgScore' => round($avgScore ?? 0, 1),
            'passCount' => $passCount,
        ];
    }
}; ?>

<div class="p-6 space-y-6">
    <x-ui.header
        :title="__('Rekap Nilai Ulangan Online')"
        :subtitle="__('Dashboard rekap nilai dan statistik ulangan siswa daring per semester.')"
        separator
    />

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-ui.card shadow>
            <div class="text-center">
                <div class="text-3xl font-bold text-slate-700 dark:text-white">{{ $totalSubmissions }}</div>
                <div class="text-sm text-slate-500 mt-1">{{ __('Total Pengerjaan') }}</div>
            </div>
        </x-ui.card>
        <x-ui.card shadow>
            <div class="text-center">
                <div class="text-3xl font-bold text-emerald-600">{{ $gradedCount }}</div>
                <div class="text-sm text-slate-500 mt-1">{{ __('Sudah Dinilai') }}</div>
            </div>
        </x-ui.card>
        <x-ui.card shadow>
            <div class="text-center">
                <div class="text-3xl font-bold text-blue-600">{{ $avgScore }}</div>
                <div class="text-sm text-slate-500 mt-1">{{ __('Rata-rata Nilai') }}</div>
            </div>
        </x-ui.card>
        <x-ui.card shadow>
            <div class="text-center">
                <div class="text-3xl font-bold text-amber-600">{{ $passCount }}</div>
                <div class="text-sm text-slate-500 mt-1">{{ __('Lulus KKM') }}</div>
            </div>
        </x-ui.card>
    </div>

    {{-- Filters --}}
    <x-ui.card shadow>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3">
            <x-ui.select
                wire:model.live="filterAcademicYear"
                :placeholder="__('Tahun Ajaran Aktif')"
                :options="$academicYears"
                option-label="name"
            />
            <x-ui.select
                wire:model.live="filterSemester"
                :placeholder="__('Semua Semester')"
                :options="[
                    ['id' => 'Ganjil', 'name' => __('Semester Ganjil')],
                    ['id' => 'Genap', 'name' => __('Semester Genap')],
                ]"
                option-label="name"
            />
            <x-ui.select wire:model.live="filterClassroom" :placeholder="__('Semua Kelas')" :options="$classrooms" option-label="name" />
            <x-ui.select wire:model.live="filterSubject" :placeholder="__('Semua Mata Pelajaran')" :options="$subjects" option-label="name" />
            <x-ui.select wire:model.live="filterExam" :placeholder="__('Semua Ulangan')" :options="$exams" option-label="name" />
            <x-ui.select
                wire:model.live="filterStatus"
                :placeholder="__('Semua Status')"
                :options="[
                    ['id' => 'in_progress', 'name' => __('Sedang Mengerjakan')],
                    ['id' => 'submitted', 'name' => __('Sudah Submit')],
                    ['id' => 'graded', 'name' => __('Sudah Dinilai')],
                ]"
                option-label="name"
            />
        </div>
    </x-ui.card>

    {{-- Table --}}
    <x-ui.card shadow padding="false">
        <x-ui.table
            :headers="[
                ['key' => 'student', 'label' => __('Siswa')],
                ['key' => 'exam', 'label' => __('Ulangan')],
                ['key' => 'semester', 'label' => __('Semester')],
                ['key' => 'subject', 'label' => __('Mapel')],
                ['key' => 'classroom', 'label' => __('Kelas')],
                ['key' => 'score', 'label' => __('Nilai')],
                ['key' => 'status', 'label' => __('Status')],
                ['key' => 'submitted_at', 'label' => __('Waktu Submit')],
                ['key' => 'actions', 'label' => '', 'class' => 'text-right'],
            ]"
            :rows="$submissions"
        >
            @scope('cell_student', $submission)
                <span class="font-medium text-slate-900 dark:text-white">{{ $submission->student?->name }}</span>
            @endscope

            @scope('cell_exam', $submission)
                <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $submission->exam?->title }}</span>
            @endscope

            @scope('cell_semester', $submission)
                <x-ui.badge :label="$submission->exam?->semester ?? '-'" variant="info" flat size="xs" />
            @endscope

            @scope('cell_subject', $submission)
                <span class="text-sm">{{ $submission->exam?->subject?->name }}</span>
            @endscope

            @scope('cell_classroom', $submission)
                <span class="text-sm">{{ $submission->exam?->classroom?->name }}</span>
            @endscope

            @scope('cell_score', $submission)
                @if($submission->total_score !== null)
                    @php
                        $passing = $submission->exam?->passing_grade ?? 70;
                        $passed = $submission->total_score >= $passing;
                    @endphp
                    <span class="font-bold {{ $passed ? 'text-emerald-600' : 'text-red-600' }}">
                        {{ number_format((float) $submission->total_score, 1) }}
                    </span>
                @else
                    <span class="text-slate-400">-</span>
                @endif
            @endscope

            @scope('cell_status', $submission)
                @php
                    $statusMap = [
                        'in_progress' => ['Mengerjakan', 'amber'],
                        'submitted' => ['Disubmit', 'info'],
                        'graded' => ['Dinilai', 'success'],
                    ];
                    $info = $statusMap[$submission->status] ?? ['Unknown', 'secondary'];
                @endphp
                <x-ui.badge :label="$info[0]" :variant="$info[1]" flat size="xs" />
            @endscope

            @scope('cell_submitted_at', $submission)
                <span class="text-sm text-slate-500">
                    {{ $submission->submitted_at?->format('d M Y H:i') ?? '-' }}
                </span>
            @endscope

            @scope('cell_actions', $submission)
                <x-ui.button
                    icon="o-eye"
                    :link="route('admin.elearning.submission-detail', $submission->id)"
                    ghost
                    :title="__('Lihat Detail')"
                />
            @endscope
        </x-ui.table>
    </x-ui.card>

    <div class="mt-4">
        {{ $submissions->links() }}
    </div>
</div>
