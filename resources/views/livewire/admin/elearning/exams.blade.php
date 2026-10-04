<?php

declare(strict_types=1);

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\MaterialChapter;
use App\Models\OnlineExam;
use App\Models\Subject;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;
    use WithFileUploads;

    public string $search = '';
    public ?int $filterClassroom = null;
    public ?int $filterSubject = null;
    public ?int $filterChapter = null;

    // Form fields
    public bool $examModal = false;
    public ?OnlineExam $editing = null;
    public ?int $subject_id = null;
    public ?int $chapter_id = null;
    public ?int $classroom_id = null;
    public ?int $academic_year_id = null;
    public string $semester = 'Ganjil';
    public string $title = '';
    public string $description = '';
    public string $exam_type = 'quiz'; // quiz, daily, midterm, final
    public ?int $duration_minutes = 30;
    public ?string $start_time = null;
    public ?string $end_time = null;
    public bool $is_published = false;
    public int $passing_grade = 70;
    public bool $shuffle_questions = false;
    public $attachmentFile = null;
    public ?string $currentAttachmentName = null;

    public function rules(): array
    {
        return [
            'subject_id' => ['required', 'exists:subjects,id'],
            'chapter_id' => ['nullable', 'exists:material_chapters,id'],
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester' => ['required', 'in:Ganjil,Genap'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'exam_type' => ['required', 'in:quiz,daily,midterm,final'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date', 'after:start_time'],
            'is_published' => ['boolean'],
            'passing_grade' => ['required', 'integer', 'min:0', 'max:100'],
            'shuffle_questions' => ['boolean'],
            'attachmentFile' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function createNew(): void
    {
        $this->reset(['subject_id', 'chapter_id', 'classroom_id', 'academic_year_id', 'semester', 'title', 'description', 'exam_type', 'duration_minutes', 'start_time', 'end_time', 'is_published', 'passing_grade', 'shuffle_questions', 'editing', 'attachmentFile', 'currentAttachmentName']);
        $activeYear = AcademicYear::where('is_active', true)->first();
        $this->academic_year_id = $activeYear?->id;
        $this->semester = $activeYear?->active_semester ?? 'Ganjil';
        $this->duration_minutes = 30;
        $this->passing_grade = 70;
        $this->exam_type = 'quiz';
        $this->examModal = true;
    }

    public function edit(OnlineExam $exam): void
    {
        $this->editing = $exam;
        $this->subject_id = $exam->subject_id;
        $this->chapter_id = $exam->chapter_id;
        $this->classroom_id = $exam->classroom_id;
        $this->academic_year_id = $exam->academic_year_id;
        $this->semester = $exam->semester;
        $this->title = $exam->title;
        $this->description = $exam->description ?? '';
        $this->exam_type = $exam->exam_type;
        $this->duration_minutes = $exam->duration_minutes;
        $this->start_time = $exam->start_time?->format('Y-m-d\TH:i');
        $this->end_time = $exam->end_time?->format('Y-m-d\TH:i');
        $this->is_published = $exam->is_published;
        $this->passing_grade = $exam->passing_grade;
        $this->shuffle_questions = $exam->shuffle_questions;
        $this->attachmentFile = null;
        $this->currentAttachmentName = $exam->attachment_name;
        $this->examModal = true;
    }

    public function removeAttachment(): void
    {
        if ($this->editing && $this->editing->attachment_path) {
            Storage::disk('private')->delete($this->editing->attachment_path);
            $this->editing->update([
                'attachment_path' => null,
                'attachment_name' => null,
            ]);
            $this->currentAttachmentName = null;
        }
        $this->attachmentFile = null;
    }

    public function save(): void
    {
        $validated = $this->validate();

        $data = [
            'subject_id' => $this->subject_id,
            'chapter_id' => $this->chapter_id,
            'classroom_id' => $this->classroom_id,
            'academic_year_id' => $this->academic_year_id,
            'semester' => $this->semester,
            'title' => $this->title,
            'description' => $this->description,
            'exam_type' => $this->exam_type,
            'duration_minutes' => $this->duration_minutes,
            'start_time' => $this->start_time ?: null,
            'end_time' => $this->end_time ?: null,
            'is_published' => $this->is_published,
            'passing_grade' => $this->passing_grade,
            'shuffle_questions' => $this->shuffle_questions,
        ];

        if ($this->attachmentFile) {
            if ($this->editing && $this->editing->attachment_path) {
                Storage::disk('private')->delete($this->editing->attachment_path);
            }
            $storedPath = $this->attachmentFile->store('exam-documents', 'private');
            $data['attachment_path'] = $storedPath;
            $data['attachment_name'] = $this->attachmentFile->getClientOriginalName();
        }

        if ($this->editing) {
            $this->editing->update($data);
        } else {
            $data['created_by'] = auth()->id();
            OnlineExam::create($data);
        }

        $this->reset(['subject_id', 'chapter_id', 'classroom_id', 'academic_year_id', 'semester', 'title', 'description', 'exam_type', 'duration_minutes', 'start_time', 'end_time', 'is_published', 'passing_grade', 'shuffle_questions', 'editing', 'attachmentFile', 'currentAttachmentName']);
        $this->examModal = false;
    }

    public function togglePublish(OnlineExam $exam): void
    {
        $exam->update(['is_published' => !$exam->is_published]);
    }

    public function delete(OnlineExam $exam): void
    {
        if ($exam->attachment_path) {
            Storage::disk('private')->delete($exam->attachment_path);
        }
        $exam->delete();
    }

    public function with(): array
    {
        $query = OnlineExam::query()
            ->with(['subject', 'chapter', 'classroom', 'academicYear', 'creator'])
            ->withCount(['questions', 'submissions'])
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->filterClassroom, fn ($q) => $q->where('classroom_id', $this->filterClassroom))
            ->when($this->filterSubject, fn ($q) => $q->where('subject_id', $this->filterSubject))
            ->when($this->filterChapter, fn ($q) => $q->where('chapter_id', $this->filterChapter))
            ->latest();

        $availableChapters = MaterialChapter::when($this->subject_id, fn($q) => $q->where('subject_id', $this->subject_id))
            ->when($this->classroom_id, fn($q) => $q->where('classroom_id', $this->classroom_id))
            ->orderBy('order')
            ->get()
            ->map(fn ($c) => ['id' => $c->id, 'name' => 'Bab ' . $c->order . ' - ' . $c->title]);

        return [
            'exams' => $query->paginate(10),
            'classrooms' => Classroom::whereHas('academicYear', fn ($q) => $q->where('is_active', true))
                ->with('level')
                ->orderBy('name')
                ->get()
                ->map(fn ($c) => ['id' => $c->id, 'name' => ($c->level?->name ?? '') . ' - ' . $c->name]),
            'subjects' => Subject::orderBy('name')->get()->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]),
            'availableChapters' => $availableChapters,
            'academicYears' => AcademicYear::latest()->get()->map(fn ($y) => ['id' => $y->id, 'name' => $y->name]),
        ];
    }
}; ?>

<div class="p-6 space-y-6">
    <x-ui.header :title="__('Kuis & Ujian Online')" :subtitle="__('Kelola kuis bab, ulangan harian, UTS, dan UAS.')" separator>
        <x-slot:actions>
            <x-ui.button :label="__('Tambah Ujian / Kuis')" icon="o-plus" class="btn-primary" wire:click="createNew" />
        </x-slot:actions>
    </x-ui.header>

    {{-- Filters --}}
    <x-ui.card shadow>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-ui.input wire:model.live.debounce.300ms="search" :placeholder="__('Cari ujian...')" icon="o-magnifying-glass" />
            <x-ui.select wire:model.live="filterClassroom" :placeholder="__('Semua Kelas')" :options="$classrooms" option-label="name" />
            <x-ui.select wire:model.live="filterSubject" :placeholder="__('Semua Mata Pelajaran')" :options="$subjects" option-label="name" />
        </div>
    </x-ui.card>

    {{-- Table --}}
    <x-ui.card shadow padding="false">
        <x-ui.table
            :headers="[
                ['key' => 'title', 'label' => __('Judul')],
                ['key' => 'exam_type', 'label' => __('Tipe')],
                ['key' => 'chapter', 'label' => __('Bab / Modul')],
                ['key' => 'subject', 'label' => __('Mapel')],
                ['key' => 'classroom', 'label' => __('Kelas')],
                ['key' => 'questions_count', 'label' => __('Soal')],
                ['key' => 'submissions_count', 'label' => __('Peserta')],
                ['key' => 'status', 'label' => __('Status')],
                ['key' => 'actions', 'label' => '', 'class' => 'text-right'],
            ]"
            :rows="$exams"
        >
            @scope('cell_title', $exam)
                <div>
                    <span class="font-semibold text-slate-900 dark:text-white">{{ $exam->title }}</span>
                    @if($exam->duration_minutes)
                        <div class="text-xs text-slate-500">{{ $exam->duration_minutes }} menit</div>
                    @endif
                </div>
            @endscope

            @scope('cell_exam_type', $exam)
                @php
                    $types = [
                        'quiz' => ['label' => 'Kuis Bab', 'variant' => 'info'],
                        'daily' => ['label' => 'Ulangan Harian', 'variant' => 'neutral'],
                        'midterm' => ['label' => 'UTS', 'variant' => 'warning'],
                        'final' => ['label' => 'UAS', 'variant' => 'error'],
                    ];
                    $t = $types[$exam->exam_type] ?? ['label' => $exam->exam_type, 'variant' => 'neutral'];
                @endphp
                <x-ui.badge :label="$t['label']" :variant="$t['variant']" flat size="xs" />
            @endscope

            @scope('cell_chapter', $exam)
                @if($exam->chapter)
                    <span class="text-sm font-medium">Bab {{ $exam->chapter->order }}: {{ $exam->chapter->title }}</span>
                @else
                    <span class="text-xs text-slate-400 italic">Tanpa Bab</span>
                @endif
            @endscope

            @scope('cell_subject', $exam)
                <span class="text-sm">{{ $exam->subject?->name }}</span>
            @endscope

            @scope('cell_classroom', $exam)
                <span class="text-sm">{{ $exam->classroom?->name }}</span>
            @endscope

            @scope('cell_questions_count', $exam)
                <span class="text-sm font-medium">{{ $exam->questions_count }}</span>
            @endscope

            @scope('cell_submissions_count', $exam)
                <span class="text-sm">{{ $exam->submissions_count }}</span>
            @endscope

            @scope('cell_status', $exam)
                @if($exam->is_published)
                    <x-ui.badge :label="__('Aktif')" variant="success" flat size="xs" />
                @else
                    <x-ui.badge :label="__('Draft')" variant="amber" flat size="xs" />
                @endif
            @endscope

            @scope('cell_actions', $exam)
                <div class="flex justify-end gap-1">
                    <x-ui.button
                        icon="o-list-bullet"
                        :link="route('admin.elearning.exam-questions', $exam->id)"
                        ghost
                        :title="__('Kelola Soal')"
                    />
                    <x-ui.button
                        :icon="$exam->is_published ? 'o-eye-slash' : 'o-eye'"
                        wire:click="togglePublish({{ $exam->id }})"
                        ghost
                    />
                    <x-ui.button icon="o-pencil-square" wire:click="edit({{ $exam->id }})" ghost />
                    <x-ui.button
                        icon="o-trash"
                        class="text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20"
                        wire:confirm="{{ __('Yakin ingin menghapus ulangan ini? Semua soal dan jawaban akan ikut terhapus.') }}"
                        wire:click="delete({{ $exam->id }})"
                        ghost
                    />
                </div>
            @endscope
        </x-ui.table>
    </x-ui.card>

    <div class="mt-4">
        {{ $exams->links() }}
    </div>

    {{-- Modal --}}
    <x-ui.modal wire:model="examModal" persistent class="max-w-3xl">
        <x-ui.header :title="$editing ? __('Edit Ujian / Kuis') : __('Tambah Ujian / Kuis Baru')" :subtitle="__('Masukkan detail ujian atau kuis bab online.')" separator />

        <form wire:submit="save" class="space-y-6">
            <x-ui.input wire:model="title" :label="__('Judul Ujian / Kuis')" required />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui.select wire:model.live="classroom_id" :label="__('Kelas')" :options="$classrooms" option-label="name" required />
                <x-ui.select wire:model.live="subject_id" :label="__('Mata Pelajaran')" :options="$subjects" option-label="name" required />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui.select
                    wire:model="exam_type"
                    :label="__('Tipe Ujian')"
                    :options="[
                        ['id' => 'quiz', 'name' => __('Kuis Bab / Modul')],
                        ['id' => 'daily', 'name' => __('Ulangan Harian')],
                        ['id' => 'midterm', 'name' => __('Ujian Tengah Semester (UTS)')],
                        ['id' => 'final', 'name' => __('Ujian Akhir Semester (UAS)')],
                    ]"
                    option-label="name"
                    required
                />

                <x-ui.select
                    wire:model="chapter_id"
                    :label="__('Modul / Bab (Khusus Kuis Bab)')"
                    :options="$availableChapters"
                    option-label="name"
                    placeholder="-- Tanpa Bab / UTS / UAS --"
                />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-ui.select wire:model="academic_year_id" :label="__('Tahun Ajaran')" :options="$academicYears" option-label="name" required />
                <x-ui.select
                    wire:model="semester"
                    :label="__('Semester')"
                    :options="[['id' => 'Ganjil', 'name' => __('Semester Ganjil')], ['id' => 'Genap', 'name' => __('Semester Genap')]]"
                    option-label="name"
                    required
                />
                <x-ui.input wire:model="duration_minutes" :label="__('Durasi (menit)')" type="number" min="1" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui.input wire:model="start_time" :label="__('Waktu Mulai')" type="datetime-local" />
                <x-ui.input wire:model="end_time" :label="__('Waktu Selesai')" type="datetime-local" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui.input wire:model="passing_grade" :label="__('KKM / Passing Grade')" type="number" min="0" max="100" required />
                <div class="flex items-end pb-2">
                    <x-ui.checkbox wire:model="shuffle_questions" :label="__('Acak urutan soal')" />
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('Deskripsi / Petunjuk') }}</label>
                <textarea wire:model="description" rows="3" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white focus:border-emerald-500 focus:ring-emerald-500" placeholder="{{ __('Petunjuk pengerjaan kuis/ujian...') }}"></textarea>
            </div>

            {{-- Lampiran Berkas Soal --}}
            <div class="p-4 rounded-xl border border-dashed border-slate-300 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/40 space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">
                            {{ __('Berkas Lampiran Soal (Word / PDF)') }}
                        </label>
                        <p class="text-xs text-slate-500">
                            {{ __('Unggah naskah soal lengkap (.pdf, .doc, .docx maks 10MB) yang dapat diunduh siswa.') }}
                        </p>
                    </div>
                    @if($editing && $editing->attachment_path)
                        <a
                            href="{{ route('elearning.exams.download-attachment', $editing->id) }}"
                            target="_blank"
                            class="inline-flex items-center gap-1 text-xs text-emerald-600 hover:text-emerald-700 font-medium"
                        >
                            <x-ui.icon name="o-arrow-down-tray" class="w-4 h-4" />
                            {{ __('Unduh Berkas Saat Ini') }}
                        </a>
                    @endif
                </div>

                @if($currentAttachmentName)
                    <div class="flex items-center justify-between p-2.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 text-xs">
                        <span class="flex items-center gap-2 text-emerald-800 dark:text-emerald-300 font-medium truncate">
                            <x-ui.icon name="o-document-text" class="w-4 h-4 shrink-0 text-emerald-600" />
                            <span class="truncate">{{ $currentAttachmentName }}</span>
                        </span>
                        <x-ui.button
                            icon="o-trash"
                            class="text-red-500 hover:text-red-700 btn-xs"
                            ghost
                            wire:click="removeAttachment"
                            wire:confirm="{{ __('Hapus berkas lampiran ini?') }}"
                        />
                    </div>
                @endif

                <input
                    type="file"
                    wire:model="attachmentFile"
                    accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                    class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-500 file:text-white hover:file:bg-emerald-600 cursor-pointer"
                />

                <div wire:loading wire:target="attachmentFile" class="text-xs text-emerald-600 flex items-center gap-1.5 font-medium">
                    <span class="animate-spin inline-block w-3.5 h-3.5 border-2 border-emerald-600 border-t-transparent rounded-full"></span>
                    {{ __('Mengunggah berkas soal...') }}
                </div>
                @error('attachmentFile') <span class="text-xs text-red-500 font-medium">{{ $message }}</span> @enderror
            </div>

            <x-ui.checkbox wire:model="is_published" :label="__('Terbitkan sekarang')" />

            <div class="flex justify-end gap-2 pt-4">
                <x-ui.button :label="__('Batal')" ghost @click="show = false" />
                <x-ui.button :label="__('Simpan')" type="submit" class="btn-primary" spinner="save" />
            </div>
        </form>
    </x-ui.modal>
</div>
