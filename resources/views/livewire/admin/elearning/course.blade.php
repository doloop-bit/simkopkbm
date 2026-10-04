<?php

declare(strict_types=1);

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\MaterialChapter;
use App\Models\OnlineExam;
use App\Models\OnlineMaterial;
use App\Models\Subject;
use App\Services\ElearningCourseCloneService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.app')] class extends Component {
    use WithFileUploads;

    #[Url]
    public ?int $academicYearId = null;

    #[Url]
    public ?string $selectedSemester = null;

    #[Url]
    public ?int $classroomId = null;

    #[Url]
    public ?int $subjectId = null;

    public bool $chapterModal = false;
    public ?int $editingChapterId = null;
    public string $chapterTitle = '';
    public string $chapterDescription = '';

    public bool $materialModal = false;
    public ?int $editingMaterialId = null;
    public ?int $materialChapterId = null;
    public string $materialType = 'text';
    public string $materialTitle = '';
    public string $materialContent = '';
    public ?string $videoUrl = null;
    public ?string $transcript = null;
    public $uploadFiles = [];

    public bool $examModal = false;
    public ?int $editingExamId = null;
    public ?int $examChapterId = null;
    public string $examType = 'quiz';
    public string $examTitle = '';
    public ?int $durationMinutes = 30;
    public int $passingGrade = 70;

    // Clone Course Modal
    public bool $cloneModal = false;
    public ?int $cloneSourceYearId = null;
    public string $cloneSourceSemester = 'Ganjil';
    public ?int $cloneSourceClassroomId = null;
    public ?int $cloneSourceSubjectId = null;

    public function updatedAcademicYearId(): void
    {
        $this->classroomId = null;
        $this->subjectId = null;
    }

    public function updatedClassroomId(): void
    {
        $this->subjectId = null;
    }

    public function setSemester(string $semester): void
    {
        if (in_array($semester, ['Ganjil', 'Genap'], true)) {
            $this->selectedSemester = $semester;
        }
    }

    public function openSubject(int $subjectId): void
    {
        $this->subjectId = $subjectId;
    }

    public function backToSubjects(): void
    {
        $this->subjectId = null;
    }

    /**
     * @return array{0: AcademicYear|null, 1: string}
     */
    protected function activeTerm(): array
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        $year = $this->academicYearId ? AcademicYear::find($this->academicYearId) : $activeYear;
        if (! $year && $activeYear) {
            $year = $activeYear;
        }

        $semester = $this->selectedSemester ?? $year?->active_semester ?? 'Ganjil';

        return [$year, $semester];
    }

    /**
     * @return array<string, int|string>
     */
    protected function scope(): array
    {
        [$year, $semester] = $this->activeTerm();

        abort_unless($year && $this->classroomId && $this->subjectId, 422);

        return [
            'subject_id' => $this->subjectId,
            'classroom_id' => $this->classroomId,
            'academic_year_id' => $year->id,
            'semester' => $semester,
        ];
    }

    public function openCloneModal(): void
    {
        [$year, $semester] = $this->activeTerm();
        $this->cloneSourceYearId = $year?->id;
        $this->cloneSourceSemester = $semester === 'Ganjil' ? 'Genap' : 'Ganjil';
        $this->cloneSourceClassroomId = $this->classroomId;
        $this->cloneSourceSubjectId = $this->subjectId;
        $this->cloneModal = true;
    }

    public function executeClone(ElearningCourseCloneService $cloneService): void
    {
        $this->validate([
            'cloneSourceYearId' => ['required', 'exists:academic_years,id'],
            'cloneSourceSemester' => ['required', 'in:Ganjil,Genap'],
            'cloneSourceClassroomId' => ['required', 'exists:classrooms,id'],
            'cloneSourceSubjectId' => ['required', 'exists:subjects,id'],
        ]);

        [$targetYear, $targetSemester] = $this->activeTerm();

        abort_unless($targetYear && $this->classroomId && $this->subjectId, 422);

        // Cegah salin ke target yang sama persis
        if (
            (int) $this->cloneSourceYearId === (int) $targetYear->id &&
            $this->cloneSourceSemester === $targetSemester &&
            (int) $this->cloneSourceClassroomId === (int) $this->classroomId &&
            (int) $this->cloneSourceSubjectId === (int) $this->subjectId
        ) {
            session()->flash('error', __('Sumber materi dan tujuan salin tidak boleh sama persis.'));

            return;
        }

        $source = [
            'subject_id' => (int) $this->cloneSourceSubjectId,
            'classroom_id' => (int) $this->cloneSourceClassroomId,
            'academic_year_id' => (int) $this->cloneSourceYearId,
            'semester' => $this->cloneSourceSemester,
        ];

        $target = [
            'subject_id' => (int) $this->subjectId,
            'classroom_id' => (int) $this->classroomId,
            'academic_year_id' => (int) $targetYear->id,
            'semester' => $targetSemester,
            'created_by' => (int) auth()->id(),
        ];

        $result = $cloneService->cloneCourse($source, $target);

        $totalCopied = $result['handbooks_count'] + $result['chapters_count'] + $result['materials_count'] + $result['exams_count'];

        if ($totalCopied === 0) {
            session()->flash('error', __('Tidak ada modul/materi pada sumber yang dipilih untuk disalin.'));
        } else {
            session()->flash('success', __(
                'Berhasil menyalin materi: :chapters bab, :materials materi, dan :exams kuis/evaluasi (:questions butir soal). Riwayat nilai siswa tahun lalu tetap aman.',
                [
                    'chapters' => $result['chapters_count'],
                    'materials' => $result['materials_count'] + $result['handbooks_count'],
                    'exams' => $result['exams_count'],
                    'questions' => $result['questions_count'],
                ]
            ));
        }

        $this->cloneModal = false;
    }

    public function createChapter(): void
    {
        $this->reset(['editingChapterId', 'chapterTitle', 'chapterDescription']);
        $this->chapterModal = true;
    }

    public function editChapter(int $id): void
    {
        $chapter = MaterialChapter::findOrFail($id);
        $this->editingChapterId = $chapter->id;
        $this->chapterTitle = $chapter->title;
        $this->chapterDescription = $chapter->description ?? '';
        $this->chapterModal = true;
    }

    public function saveChapter(): void
    {
        $this->validate([
            'chapterTitle' => ['required', 'string', 'max:255'],
            'chapterDescription' => ['nullable', 'string'],
        ]);

        if ($this->editingChapterId) {
            MaterialChapter::findOrFail($this->editingChapterId)->update([
                'title' => $this->chapterTitle,
                'description' => $this->chapterDescription,
            ]);
        } else {
            $scope = $this->scope();
            MaterialChapter::create($scope + [
                'title' => $this->chapterTitle,
                'description' => $this->chapterDescription,
                'order' => ((int) MaterialChapter::where($scope)->max('order')) + 1,
                'is_published' => true,
                'published_at' => now(),
                'created_by' => auth()->id(),
            ]);
        }

        $this->chapterModal = false;
    }

    public function deleteChapter(int $id): void
    {
        $chapter = MaterialChapter::with('materials')->findOrFail($id);

        foreach ($chapter->materials as $material) {
            $this->deleteMaterialFiles($material);
        }

        $chapter->delete();
    }

    public function toggleChapter(int $id): void
    {
        $chapter = MaterialChapter::findOrFail($id);
        $chapter->update([
            'is_published' => ! $chapter->is_published,
            'published_at' => ! $chapter->is_published ? now() : null,
        ]);
    }

    public function moveChapter(int $id, string $direction): void
    {
        $chapter = MaterialChapter::findOrFail($id);
        $siblings = MaterialChapter::where($this->scope())->orderBy('order')->orderBy('id')->get();
        $this->swapOrder($siblings, $id, $direction);
    }

    public function createMaterial(?int $chapterId = null): void
    {
        $this->reset(['editingMaterialId', 'materialTitle', 'materialContent', 'videoUrl', 'transcript', 'uploadFiles']);
        $this->materialChapterId = $chapterId;
        $this->materialType = $chapterId ? 'text' : 'handbook';
        $this->materialModal = true;
    }

    public function editMaterial(int $id): void
    {
        $material = OnlineMaterial::findOrFail($id);
        $this->editingMaterialId = $material->id;
        $this->materialChapterId = $material->chapter_id;
        $this->materialType = $material->type ?? 'text';
        $this->materialTitle = $material->title;
        $this->materialContent = $material->content ?? '';
        $this->videoUrl = $material->video_url;
        $this->transcript = $material->transcript;
        $this->uploadFiles = [];
        $this->materialModal = true;
    }

    public function saveMaterial(): void
    {
        $this->validate([
            'materialTitle' => ['required', 'string', 'max:255'],
            'materialType' => ['required', 'in:handbook,text,slides,video'],
            'materialContent' => ['nullable', 'string'],
            'videoUrl' => ['nullable', 'url'],
            'transcript' => ['nullable', 'string'],
            'uploadFiles.*' => ['nullable', 'file', 'max:51200'],
        ]);

        $existing = $this->editingMaterialId ? OnlineMaterial::findOrFail($this->editingMaterialId) : null;
        $attachments = $existing?->attachments ?? [];

        foreach ($this->uploadFiles as $file) {
            $attachments[] = [
                'path' => $file->store('elearning/materials', 'public'),
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
            ];
        }

        $data = [
            'title' => $this->materialTitle,
            'type' => $this->materialType,
            'content' => $this->materialContent,
            'video_url' => $this->materialType === 'video' ? $this->videoUrl : null,
            'transcript' => $this->materialType === 'video' ? $this->transcript : null,
            'attachments' => $attachments,
        ];

        if ($existing) {
            $existing->update($data);
        } else {
            $scope = $this->scope();
            OnlineMaterial::create($scope + $data + [
                'chapter_id' => $this->materialChapterId,
                'order' => ((int) OnlineMaterial::where($scope)->where('chapter_id', $this->materialChapterId)->max('order')) + 1,
                'is_published' => true,
                'published_at' => now(),
                'created_by' => auth()->id(),
            ]);
        }

        $this->materialModal = false;
    }

    public function removeAttachment(int $materialId, int $index): void
    {
        $material = OnlineMaterial::findOrFail($materialId);
        $attachments = $material->attachments ?? [];

        if (isset($attachments[$index])) {
            Storage::disk('public')->delete($attachments[$index]['path']);
            array_splice($attachments, $index, 1);
            $material->update(['attachments' => $attachments]);
        }
    }

    public function toggleMaterial(int $id): void
    {
        $material = OnlineMaterial::findOrFail($id);
        $material->update([
            'is_published' => ! $material->is_published,
            'published_at' => ! $material->is_published ? now() : null,
        ]);
    }

    public function deleteMaterial(int $id): void
    {
        $material = OnlineMaterial::findOrFail($id);
        $this->deleteMaterialFiles($material);
        $material->delete();
    }

    public function moveMaterial(int $id, string $direction): void
    {
        $material = OnlineMaterial::findOrFail($id);
        $siblings = OnlineMaterial::where($this->scope())
            ->where('chapter_id', $material->chapter_id)
            ->orderBy('order')
            ->orderBy('id')
            ->get();
        $this->swapOrder($siblings, $id, $direction);
    }

    protected function deleteMaterialFiles(OnlineMaterial $material): void
    {
        foreach ($material->attachments ?? [] as $attachment) {
            Storage::disk('public')->delete($attachment['path']);
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \Illuminate\Database\Eloquent\Model>  $siblings
     */
    protected function swapOrder($siblings, int $id, string $direction): void
    {
        $ids = $siblings->pluck('id')->values()->all();
        $position = array_search($id, $ids, true);
        $target = $direction === 'up' ? $position - 1 : $position + 1;

        if ($position === false || ! isset($ids[$target])) {
            return;
        }

        [$ids[$position], $ids[$target]] = [$ids[$target], $ids[$position]];

        foreach ($siblings as $item) {
            $item->update(['order' => array_search($item->id, $ids, true) + 1]);
        }
    }

    public function createExam(?int $chapterId = null, string $type = 'quiz'): void
    {
        $this->reset(['editingExamId', 'examTitle']);
        $this->examChapterId = $chapterId;
        $this->examType = $type;
        $this->durationMinutes = 30;
        $this->passingGrade = 70;
        $this->examTitle = match ($type) {
            'midterm' => 'Ujian Tengah Semester',
            'final' => 'Ujian Akhir Semester',
            default => 'Kuis',
        };
        $this->examModal = true;
    }

    public function editExam(int $id): void
    {
        $exam = OnlineExam::findOrFail($id);
        $this->editingExamId = $exam->id;
        $this->examChapterId = $exam->chapter_id;
        $this->examType = $exam->exam_type;
        $this->examTitle = $exam->title;
        $this->durationMinutes = $exam->duration_minutes;
        $this->passingGrade = $exam->passing_grade;
        $this->examModal = true;
    }

    public function saveExam(): void
    {
        $this->validate([
            'examTitle' => ['required', 'string', 'max:255'],
            'examType' => ['required', 'in:quiz,midterm,final'],
            'durationMinutes' => ['nullable', 'integer', 'min:1'],
            'passingGrade' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $data = [
            'title' => $this->examTitle,
            'duration_minutes' => $this->durationMinutes,
            'passing_grade' => $this->passingGrade,
        ];

        if ($this->editingExamId) {
            OnlineExam::findOrFail($this->editingExamId)->update($data);
        } else {
            OnlineExam::create($this->scope() + $data + [
                'chapter_id' => $this->examChapterId,
                'exam_type' => $this->examType,
                'is_published' => false,
                'created_by' => auth()->id(),
            ]);
        }

        $this->examModal = false;
    }

    public function toggleExam(int $id): void
    {
        $exam = OnlineExam::findOrFail($id);
        $exam->update(['is_published' => ! $exam->is_published]);
    }

    public function deleteExam(int $id): void
    {
        $exam = OnlineExam::withCount('submissions')->findOrFail($id);

        if ($exam->submissions_count > 0) {
            session()->flash('error', __('Ujian ini tidak dapat dihapus karena sudah memiliki riwayat pengerjaan siswa (:count pengerjaan). Anda dapat menonaktifkan status publikasinya agar nilai siswa tetap aman.', ['count' => $exam->submissions_count]));

            return;
        }

        $exam->delete();
        session()->flash('success', __('Ujian berhasil dihapus.'));
    }

    public function with(): array
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        [$year, $semester] = $this->activeTerm();

        $academicYears = AcademicYear::orderByDesc('name')->get()->map(fn ($y) => [
            'id' => $y->id,
            'name' => $y->name.($y->is_active ? ' ('.__('Aktif').')' : ''),
        ]);

        $classrooms = Classroom::query()
            ->when($year, fn ($q) => $q->where('academic_year_id', $year->id))
            ->with('level')
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => ['id' => $c->id, 'name' => trim(($c->level?->name ?? '').' - '.$c->name, ' -')]);

        $classroom = $this->classroomId ? Classroom::with('level')->find($this->classroomId) : null;

        $subjects = collect();
        if ($classroom) {
            $phase = $classroom->level?->phase_map[$classroom->class_level] ?? null;

            $subjects = Subject::query()
                ->where(function ($q) use ($classroom, $phase) {
                    if ($phase) {
                        $q->where('phase', $phase);
                    }
                    if ($classroom->level_id) {
                        $q->orWhere('level_id', $classroom->level_id);
                    }
                    $q->orWhereNull('level_id');
                })
                ->orderBy('name')
                ->get();

            // Fallback jika tidak ada filter yang match, tampilkan seluruh mata pelajaran yang ada
            if ($subjects->isEmpty()) {
                $subjects = Subject::orderBy('name')->get();
            }
        }

        $subject = $this->subjectId ? Subject::find($this->subjectId) : null;

        $handbooks = collect();
        $chapters = collect();
        $termExams = collect();

        if ($year && $classroom && $subject) {
            $scope = [
                'subject_id' => $subject->id,
                'classroom_id' => $classroom->id,
                'academic_year_id' => $year->id,
                'semester' => $semester,
            ];

            $handbooks = OnlineMaterial::where($scope)->whereNull('chapter_id')->orderBy('order')->get();
            $chapters = MaterialChapter::where($scope)
                ->with([
                    'materials',
                    'exams' => fn ($q) => $q->where('exam_type', 'quiz')->withCount('questions')->withCount('submissions'),
                ])
                ->orderBy('order')
                ->orderBy('id')
                ->get();
            $termExams = OnlineExam::where($scope)
                ->whereNull('chapter_id')
                ->whereIn('exam_type', ['midterm', 'final'])
                ->withCount('questions')
                ->withCount('submissions')
                ->get();
        }

        // Dropdown options for clone modal
        $cloneSourceClassrooms = Classroom::query()
            ->when($this->cloneSourceYearId, fn ($q) => $q->where('academic_year_id', $this->cloneSourceYearId))
            ->with('level')
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => ['id' => $c->id, 'name' => trim(($c->level?->name ?? '').' - '.$c->name, ' -')]);

        $cloneSourceSubjects = Subject::orderBy('name')->get()->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]);

        return [
            'activeYear' => $activeYear,
            'academicYears' => $academicYears,
            'year' => $year,
            'semester' => $semester,
            'classrooms' => $classrooms,
            'classroom' => $classroom,
            'subjects' => $subjects,
            'subject' => $subject,
            'handbooks' => $handbooks,
            'chapters' => $chapters,
            'termExams' => $termExams,
            'cloneSourceClassrooms' => $cloneSourceClassrooms,
            'cloneSourceSubjects' => $cloneSourceSubjects,
        ];
    }
}; ?>

<div class="p-6 space-y-6 max-w-5xl mx-auto">
    <x-ui.header
        :title="__('Materi Pelajaran')"
        :subtitle="__('Kelola modul materi, buku pegangan, dan kuis online per semester.')"
        separator
    />

    @if(session('success'))
        <x-ui.alert variant="success" icon="o-check-circle" :title="session('success')" />
    @endif

    @if(session('error'))
        <x-ui.alert variant="error" icon="o-exclamation-triangle" :title="session('error')" />
    @endif

    @if(! $year)
        <x-ui.card shadow>
            <p class="text-sm text-slate-500">{{ __('Aktifkan tahun ajaran terlebih dahulu di menu Akademik.') }}</p>
        </x-ui.card>
    @else
        {{-- Filter & Pengaturan Semester --}}
        <x-ui.card shadow class="bg-slate-50/70 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800">
            <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
                {{-- Pemilih Tahun Ajaran & Kelas --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 flex-1 max-w-xl">
                    <x-ui.select
                        wire:model.live="academicYearId"
                        :label="__('Tahun Ajaran')"
                        :options="$academicYears"
                        option-label="name"
                        :hint="$activeYear && $year?->id === $activeYear->id ? __('Tahun ajaran aktif saat ini') : null"
                    />

                    <x-ui.select
                        wire:model.live="classroomId"
                        :label="__('Kelas')"
                        :placeholder="__('Pilih kelas')"
                        :options="$classrooms"
                        option-label="name"
                    />
                </div>

                {{-- Switcher Semester Ganjil & Genap --}}
                <div class="flex flex-col items-start md:items-end justify-center gap-1.5">
                    <label class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        {{ __('Pilihan Semester') }}
                    </label>
                    <div class="inline-flex p-1 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs">
                        <button
                            type="button"
                            wire:click="setSemester('Ganjil')"
                            class="px-4 py-2 rounded-lg text-xs font-semibold transition-all flex items-center gap-2 {{ $semester === 'Ganjil' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
                        >
                            <x-ui.icon name="o-calendar" class="w-4 h-4" />
                            <span>{{ __('Semester Ganjil') }}</span>
                            @if($activeYear && $activeYear->id === $year?->id && ($activeYear->active_semester ?? 'Ganjil') === 'Ganjil')
                                <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $semester === 'Ganjil' ? 'bg-white/20 text-white' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' }}">
                                    {{ __('Aktif') }}
                                </span>
                            @endif
                        </button>

                        <button
                            type="button"
                            wire:click="setSemester('Genap')"
                            class="px-4 py-2 rounded-lg text-xs font-semibold transition-all flex items-center gap-2 {{ $semester === 'Genap' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
                        >
                            <x-ui.icon name="o-calendar" class="w-4 h-4" />
                            <span>{{ __('Semester Genap') }}</span>
                            @if($activeYear && $activeYear->id === $year?->id && ($activeYear->active_semester ?? 'Ganjil') === 'Genap')
                                <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $semester === 'Genap' ? 'bg-white/20 text-white' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' }}">
                                    {{ __('Aktif') }}
                                </span>
                            @endif
                        </button>
                    </div>
                </div>
            </div>
        </x-ui.card>

        {{-- STEP 1: pilih mapel --}}
        @if($classroom && ! $subject)
            <div class="space-y-3">
                <div class="flex items-center justify-between text-xs text-slate-500">
                    <span>{{ __('Menampilkan mata pelajaran untuk') }}: <strong class="text-slate-700 dark:text-slate-300">{{ $classroom->name }}</strong> · <strong class="text-emerald-600">Semester {{ $semester }}</strong> ({{ $year?->name }})</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse($subjects as $item)
                        <button type="button" wire:click="openSubject({{ $item->id }})" class="text-left" wire:key="subject-{{ $item->id }}">
                            <x-ui.card shadow class="hover:shadow-lg transition-shadow h-full">
                                <div class="flex items-center gap-3">
                                    <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600">
                                        <x-ui.icon name="o-book-open" class="w-6 h-6" />
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-slate-900 dark:text-white">{{ $item->name }}</h3>
                                        <p class="text-xs text-slate-500">{{ __('Klik untuk kelola materi') }}</p>
                                    </div>
                                </div>
                            </x-ui.card>
                        </button>
                    @empty
                        <p class="text-sm text-slate-500 col-span-full">{{ __('Belum ada mata pelajaran untuk jenjang kelas ini.') }}</p>
                    @endforelse
                </div>
            </div>
        @endif

        {{-- STEP 2: susunan mapel --}}
        @if($classroom && $subject)
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <button type="button" wire:click="backToSubjects" class="inline-flex items-center gap-1 text-xs text-slate-500 hover:text-emerald-600 mb-1">
                        <x-ui.icon name="o-arrow-left" class="w-4 h-4" /> {{ __('Semua mata pelajaran') }}
                    </button>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ $subject->name }}</h2>
                        <span class="text-slate-400 font-normal">· {{ $classroom->name }}</span>
                        <x-ui.badge :label="'Semester ' . $semester . ' (' . ($year?->name ?? '') . ')'" variant="info" flat size="xs" />
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <x-ui.button
                        :label="__('Salin Materi')"
                        icon="o-document-duplicate"
                        ghost
                        size="sm"
                        class="text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/30"
                        wire:click="openCloneModal"
                        :title="__('Salin materi atau kuis dari semester / tahun ajaran lain')"
                    />
                    <x-ui.button :label="__('Tambah Bab')" icon="o-plus" class="btn-primary" wire:click="createChapter" />
                </div>
            </div>

            {{-- Buku Pegangan --}}
            <x-ui.card shadow>
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-bold text-sm uppercase tracking-wide text-slate-700 dark:text-slate-300">{{ __('Buku Pegangan') }}</h3>
                    <x-ui.button :label="__('Tambah')" icon="o-plus" ghost size="sm" wire:click="createMaterial" />
                </div>
                @forelse($handbooks as $material)
                    @include('partials.elearning.material-row', ['material' => $material, 'first' => $loop->first, 'last' => $loop->last])
                @empty
                    <p class="text-xs text-slate-400">{{ __('Belum ada buku pegangan pada Semester :semester ini.', ['semester' => $semester]) }}</p>
                @endforelse
            </x-ui.card>

            {{-- Bab --}}
            @foreach($chapters as $chapter)
                <x-ui.card shadow wire:key="chapter-{{ $chapter->id }}">
                    <div class="flex flex-wrap items-center justify-between gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white">
                                {{ __('Bab') }} {{ $loop->iteration }}: {{ $chapter->title }}
                                @unless($chapter->is_published)
                                    <x-ui.badge :label="__('Draf')" variant="amber" flat size="xs" />
                                @endunless
                            </h3>
                            @if($chapter->description)
                                <p class="text-xs text-slate-500">{{ $chapter->description }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-1">
                            <x-ui.button icon="o-arrow-up" ghost size="sm" wire:click="moveChapter({{ $chapter->id }}, 'up')" :disabled="$loop->first" />
                            <x-ui.button icon="o-arrow-down" ghost size="sm" wire:click="moveChapter({{ $chapter->id }}, 'down')" :disabled="$loop->last" />
                            <x-ui.button :icon="$chapter->is_published ? 'o-eye' : 'o-eye-slash'" ghost size="sm" wire:click="toggleChapter({{ $chapter->id }})" :title="__('Terbit / Draf')" />
                            <x-ui.button icon="o-pencil-square" ghost size="sm" wire:click="editChapter({{ $chapter->id }})" />
                            <x-ui.button icon="o-trash" ghost size="sm" class="text-red-600" wire:confirm="{{ __('Hapus bab beserta seluruh materinya?') }}" wire:click="deleteChapter({{ $chapter->id }})" />
                        </div>
                    </div>

                    <div class="py-2 space-y-1">
                        @foreach($chapter->materials as $material)
                            @include('partials.elearning.material-row', ['material' => $material, 'first' => $loop->first, 'last' => $loop->last])
                        @endforeach

                        @foreach($chapter->exams as $exam)
                            @include('partials.elearning.exam-row', ['exam' => $exam])
                        @endforeach

                        @if($chapter->materials->isEmpty() && $chapter->exams->isEmpty())
                            <p class="text-xs text-slate-400">{{ __('Bab ini masih kosong.') }}</p>
                        @endif
                    </div>

                    <div class="flex gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <x-ui.button :label="__('Tambah Materi')" icon="o-document-plus" ghost size="sm" class="text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/30" wire:click="createMaterial({{ $chapter->id }})" />
                        <x-ui.button :label="__('Tambah Kuis')" icon="o-plus-circle" ghost size="sm" class="text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/30" wire:click="createExam({{ $chapter->id }}, 'quiz')" />
                    </div>
                </x-ui.card>
            @endforeach

            @if($chapters->isEmpty())
                <div class="text-center py-10 bg-slate-50 dark:bg-slate-800/40 rounded-2xl border border-dashed border-slate-200 dark:border-slate-700">
                    <x-ui.icon name="o-squares-2x2" class="w-12 h-12 mx-auto mb-2 text-slate-300 dark:text-slate-600" />
                    <h4 class="font-semibold text-slate-700 dark:text-slate-300 text-sm">{{ __('Belum ada modul / bab untuk Semester :semester', ['semester' => $semester]) }}</h4>
                    <p class="text-xs text-slate-500 mt-1 mb-3">{{ __('Mulai susun materi dengan membuat bab pertama atau salin materi dari semester lain.') }}</p>
                    <div class="flex items-center justify-center gap-2">
                        <x-ui.button :label="__('Tambah Bab Pertama')" icon="o-plus" class="btn-primary btn-sm" wire:click="createChapter" />
                        <x-ui.button :label="__('Salin dari Semester Lain')" icon="o-document-duplicate" ghost size="sm" class="text-indigo-600" wire:click="openCloneModal" />
                    </div>
                </div>
            @endif

            {{-- UTS / UAS --}}
            <x-ui.card shadow>
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3 pb-2 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <h3 class="font-bold text-sm uppercase tracking-wide text-slate-700 dark:text-slate-300 flex items-center gap-2">
                            <x-ui.icon name="o-academic-cap" class="w-4 h-4 text-emerald-600" />
                            {{ __('Evaluasi Semester (UTS / UAS) - Semester :semester', ['semester' => $semester]) }}
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">{{ __('Ujian evaluasi tengah atau akhir semester untuk siswa.') }}</p>
                    </div>
                    <div class="flex gap-2">
                        <x-ui.button :label="__('+ UTS')" icon="o-clipboard-document-check" ghost size="sm" class="text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/30" wire:click="createExam(null, 'midterm')" />
                        <x-ui.button :label="__('+ UAS')" icon="o-academic-cap" ghost size="sm" class="text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30" wire:click="createExam(null, 'final')" />
                    </div>
                </div>
                @forelse($termExams as $exam)
                    @include('partials.elearning.exam-row', ['exam' => $exam])
                @empty
                    <p class="text-xs text-slate-400">{{ __('Belum ada UTS / UAS pada Semester :semester.', ['semester' => $semester]) }}</p>
                @endforelse
            </x-ui.card>
        @endif
    @endif

    {{-- Modal Salin / Klon Materi --}}
    <x-ui.modal wire:model="cloneModal" persistent class="max-w-xl">
        <x-ui.header
            :title="__('Salin Materi & Kuis')"
            :subtitle="__('Salin struktur bab, materi pembelajaran, dan bank soal dari semester/tahun ajaran lain.')"
            separator
        />

        <div class="space-y-4">
            <div class="p-3.5 rounded-xl bg-indigo-50/70 dark:bg-indigo-950/30 border border-indigo-200/80 dark:border-indigo-800/60 text-xs text-indigo-900 dark:text-indigo-200 space-y-1">
                <div class="font-semibold flex items-center gap-1.5 text-indigo-700 dark:text-indigo-300">
                    <x-ui.icon name="o-shield-check" class="w-4 h-4" />
                    {{ __('Keamanan Riwayat Nilai & Hemat Memori') }}
                </div>
                <p>
                    {{ __('Berkas lampiran PDF/dokumen akan memakai file fisik yang sama (0 byte tambahan harddisk). Soal & materi disalin sebagai data baru khusus semester tujuan, sehingga riwayat jawaban dan nilai siswa lama tetap 100% aman.') }}
                </p>
            </div>

            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-xs">
                <span class="text-slate-500 uppercase font-semibold text-[10px] tracking-wider">{{ __('Target Tujuan Salin:') }}</span>
                <div class="font-bold text-slate-900 dark:text-white mt-0.5">
                    {{ $subject?->name }} · {{ $classroom?->name }} · <span class="text-emerald-600">Semester {{ $semester }} ({{ $year?->name }})</span>
                </div>
            </div>

            <form wire:submit="executeClone" class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <x-ui.select
                        wire:model.live="cloneSourceYearId"
                        :label="__('Tahun Ajaran Sumber')"
                        :options="$academicYears"
                        option-label="name"
                        required
                    />

                    <x-ui.select
                        wire:model.live="cloneSourceSemester"
                        :label="__('Semester Sumber')"
                        :options="[
                            ['id' => 'Ganjil', 'name' => __('Semester Ganjil')],
                            ['id' => 'Genap', 'name' => __('Semester Genap')],
                        ]"
                        option-label="name"
                        required
                    />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <x-ui.select
                        wire:model.live="cloneSourceClassroomId"
                        :label="__('Kelas Sumber')"
                        :placeholder="__('Pilih kelas sumber')"
                        :options="$cloneSourceClassrooms"
                        option-label="name"
                        required
                    />

                    <x-ui.select
                        wire:model.live="cloneSourceSubjectId"
                        :label="__('Mapel Sumber')"
                        :placeholder="__('Pilih mapel sumber')"
                        :options="$cloneSourceSubjects"
                        option-label="name"
                        required
                    />
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <x-ui.button :label="__('Batal')" ghost @click="show = false" />
                    <x-ui.button
                        :label="__('Salin Sekarang')"
                        icon="o-document-duplicate"
                        type="submit"
                        class="btn-primary"
                        spinner="executeClone"
                    />
                </div>
            </form>
        </div>
    </x-ui.modal>

    {{-- Modal bab --}}
    <x-ui.modal wire:model="chapterModal" persistent class="max-w-lg">
        <x-ui.header :title="$editingChapterId ? __('Edit Bab') : __('Tambah Bab Baru')" :subtitle="__('Masukkan judul dan deskripsi bab / modul.')" separator />
        <form wire:submit="saveChapter" class="space-y-4">
            <x-ui.input wire:model="chapterTitle" :label="__('Judul Bab')" placeholder="misal: Bab 1 - Pengenalan Aljabar" required />
            <x-ui.textarea wire:model="chapterDescription" :label="__('Deskripsi (opsional)')" rows="3" placeholder="Ringkasan kompetensi atau isi bab..." />
            <div class="flex justify-end gap-2 pt-2">
                <x-ui.button :label="__('Batal')" ghost @click="show = false" />
                <x-ui.button :label="__('Simpan')" type="submit" class="btn-primary" spinner="saveChapter" />
            </div>
        </form>
    </x-ui.modal>

    {{-- Modal materi --}}
    <x-ui.modal wire:model="materialModal" persistent class="max-w-2xl">
        <x-ui.header :title="$editingMaterialId ? __('Edit Materi') : __('Tambah Materi Baru')" :subtitle="__('Masukkan detail materi pembelajaran modul.')" separator />
        <form wire:submit="saveMaterial" class="space-y-4">
            <x-ui.input wire:model="materialTitle" :label="__('Judul Materi')" placeholder="misal: Pengenalan Variabel dan Konstanta" required />

            <x-ui.select
                wire:model.live="materialType"
                :label="__('Tipe Materi')"
                :options="[
                    ['id' => 'text', 'name' => __('Artikel / Teks')],
                    ['id' => 'slides', 'name' => __('Slide Presentasi (PDF)')],
                    ['id' => 'video', 'name' => __('Video Pembelajaran + Transkrip')],
                    ['id' => 'handbook', 'name' => __('Buku Pegangan Utama (PDF)')],
                ]"
                option-label="name"
            />

            @if($materialType === 'video')
                <div class="space-y-3 p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                    <x-ui.input wire:model="videoUrl" :label="__('Tautan Video (YouTube / Google Drive)')" placeholder="https://www.youtube.com/watch?v=..." />
                    <x-ui.textarea wire:model="transcript" :label="__('Transkrip & Ringkasan Video')" rows="4" placeholder="Tuliskan poin-poin penjelasan penting atau transkrip video di sini..." />
                </div>
            @endif

            @if($materialType !== 'slides')
                <x-ui.textarea wire:model="materialContent" :label="__('Isi / Penjelasan Materi')" rows="6" placeholder="Tuliskan materi pembelajaran lengkap di sini..." />
            @endif

            @if(in_array($materialType, ['slides', 'handbook', 'text'], true))
                <div class="space-y-2">
                    <x-ui.file
                        wire:model="uploadFiles"
                        :label="__('Berkas Dokumen / Slide (PDF)')"
                        accept=".pdf,application/pdf"
                        multiple
                        :hint="__('Pilih satu atau beberapa berkas PDF (maksimal 50MB per berkas).')"
                    />
                    <div wire:loading wire:target="uploadFiles" class="text-xs text-emerald-600 flex items-center gap-1.5 font-medium">
                        <span class="animate-spin inline-block w-3.5 h-3.5 border-2 border-emerald-600 border-t-transparent rounded-full"></span>
                        {{ __('Mengunggah berkas...') }}
                    </div>
                </div>
            @endif

            @if($editingMaterialId)
                @php($editingMaterial = \App\Models\OnlineMaterial::find($editingMaterialId))
                @if(!empty($editingMaterial?->attachments))
                    <div class="space-y-1.5 pt-2">
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Berkas Terlampir Saat Ini') }}</label>
                        @foreach($editingMaterial->attachments as $index => $attachment)
                            <div class="flex items-center justify-between text-xs p-2.5 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700" wire:key="att-{{ $index }}">
                                <span class="font-medium truncate flex items-center gap-2">
                                    <x-ui.icon name="o-paper-clip" class="w-4 h-4 text-emerald-600 shrink-0" />
                                    <span class="truncate">{{ $attachment['name'] }}</span>
                                </span>
                                <x-ui.button icon="o-trash" ghost size="xs" class="text-red-500 hover:text-red-700" wire:confirm="{{ __('Hapus file ini?') }}" wire:click="removeAttachment({{ $editingMaterialId }}, {{ $index }})" />
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif

            <div class="flex justify-end gap-2 pt-2">
                <x-ui.button :label="__('Batal')" ghost @click="show = false" />
                <x-ui.button :label="__('Simpan')" type="submit" class="btn-primary" spinner="saveMaterial" />
            </div>
        </form>
    </x-ui.modal>

    {{-- Modal kuis / ujian --}}
    <x-ui.modal wire:model="examModal" persistent class="max-w-lg">
        <x-ui.header :title="$editingExamId ? __('Edit Kuis / Ujian') : __('Tambah Kuis / Ujian')" :subtitle="__('Atur judul, durasi pengerjaan, dan KKM kelulusan.')" separator />
        <form wire:submit="saveExam" class="space-y-4">
            <x-ui.input wire:model="examTitle" :label="__('Judul Kuis / Ujian')" placeholder="misal: Kuis Evaluasi Bab 1" required />
            <div class="grid grid-cols-2 gap-4">
                <x-ui.input wire:model="durationMinutes" :label="__('Durasi (menit)')" type="number" min="1" />
                <x-ui.input wire:model="passingGrade" :label="__('Nilai Minimal (KKM)')" type="number" min="0" max="100" />
            </div>
            <div class="p-3 bg-amber-50 dark:bg-amber-950/30 rounded-xl border border-amber-200 dark:border-amber-800/60 text-xs text-amber-800 dark:text-amber-300 flex items-center gap-2">
                <x-ui.icon name="o-information-circle" class="w-5 h-5 shrink-0 text-amber-600" />
                <span>{{ __('Setelah kuis/ujian dibuat, klik tombol "Soal" pada baris kuis untuk mengisi butir pertanyaan.') }}</span>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <x-ui.button :label="__('Batal')" ghost @click="show = false" />
                <x-ui.button :label="__('Simpan')" type="submit" class="btn-primary" spinner="saveExam" />
            </div>
        </form>
    </x-ui.modal>
</div>
