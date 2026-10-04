<?php

declare(strict_types=1);

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\MaterialChapter;
use App\Models\OnlineExam;
use App\Models\OnlineMaterial;
use App\Models\Subject;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.app')] class extends Component {
    use WithFileUploads;

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

    public function updatedClassroomId(): void
    {
        $this->subjectId = null;
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
        $year = AcademicYear::where('is_active', true)->first();

        return [$year, $year?->active_semester ?? 'Ganjil'];
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
        OnlineExam::findOrFail($id)->delete();
    }

    public function with(): array
    {
        [$year, $semester] = $this->activeTerm();

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
                    'exams' => fn ($q) => $q->where('exam_type', 'quiz')->withCount('questions'),
                ])
                ->orderBy('order')
                ->orderBy('id')
                ->get();
            $termExams = OnlineExam::where($scope)
                ->whereNull('chapter_id')
                ->whereIn('exam_type', ['midterm', 'final'])
                ->withCount('questions')
                ->get();
        }

        return [
            'year' => $year,
            'semester' => $semester,
            'classrooms' => $classrooms,
            'classroom' => $classroom,
            'subjects' => $subjects,
            'subject' => $subject,
            'handbooks' => $handbooks,
            'chapters' => $chapters,
            'termExams' => $termExams,
        ];
    }
}; ?>

<div class="p-6 space-y-6 max-w-5xl mx-auto">
    <x-ui.header
        :title="__('Materi Pelajaran')"
        :subtitle="'Semester '.$semester.' - '.($year?->name ?? __('belum ada tahun ajaran aktif'))"
        separator
    />

    @if(! $year)
        <x-ui.card shadow>
            <p class="text-sm text-slate-500">{{ __('Aktifkan tahun ajaran terlebih dahulu di menu Akademik.') }}</p>
        </x-ui.card>
    @else
        <div class="max-w-sm">
            <x-ui.select wire:model.live="classroomId" :label="__('Kelas')" :placeholder="__('Pilih kelas')" :options="$classrooms" option-label="name" />
        </div>

        {{-- STEP 1: pilih mapel --}}
        @if($classroom && ! $subject)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($subjects as $item)
                    <button type="button" wire:click="openSubject({{ $item->id }})" class="text-left" wire:key="subject-{{ $item->id }}">
                        <x-ui.card shadow class="hover:shadow-lg transition-shadow h-full">
                            <div class="flex items-center gap-3">
                                <div class="p-3 rounded-xl bg-emerald-50 text-emerald-600">
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
        @endif

        {{-- STEP 2: susunan mapel --}}
        @if($classroom && $subject)
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <button type="button" wire:click="backToSubjects" class="inline-flex items-center gap-1 text-xs text-slate-500 hover:text-emerald-600 mb-1">
                        <x-ui.icon name="o-arrow-left" class="w-4 h-4" /> {{ __('Semua mata pelajaran') }}
                    </button>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ $subject->name }} <span class="text-slate-400 font-normal">· {{ $classroom->name }}</span></h2>
                </div>
                <x-ui.button :label="__('Tambah Bab')" icon="o-plus" class="btn-primary" wire:click="createChapter" />
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
                    <p class="text-xs text-slate-400">{{ __('Belum ada buku pegangan.') }}</p>
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
                    <h4 class="font-semibold text-slate-700 dark:text-slate-300 text-sm">{{ __('Belum ada modul / bab') }}</h4>
                    <p class="text-xs text-slate-500 mt-1 mb-3">{{ __('Mulai susun materi dengan membuat bab pertama pembelajaran.') }}</p>
                    <x-ui.button :label="__('Tambah Bab Pertama')" icon="o-plus" class="btn-primary btn-sm" wire:click="createChapter" />
                </div>
            @endif

            {{-- UTS / UAS --}}
            <x-ui.card shadow>
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3 pb-2 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <h3 class="font-bold text-sm uppercase tracking-wide text-slate-700 dark:text-slate-300 flex items-center gap-2">
                            <x-ui.icon name="o-academic-cap" class="w-4 h-4 text-emerald-600" />
                            {{ __('Evaluasi Semester (UTS / UAS)') }}
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
                    <p class="text-xs text-slate-400">{{ __('Belum ada UTS / UAS.') }}</p>
                @endforelse
            </x-ui.card>
        @endif
    @endif

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
