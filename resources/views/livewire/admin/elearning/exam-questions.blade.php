<?php

declare(strict_types=1);

use App\Models\OnlineExam;
use App\Models\OnlineExamQuestion;
use App\Services\HtmlSanitizerService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.app')] class extends Component {
    use WithFileUploads;

    public OnlineExam $exam;

    // Form fields
    public bool $questionModal = false;
    public ?OnlineExamQuestion $editing = null;
    public string $question_type = 'multiple_choice';
    public string $question_text = '';
    public array $options = ['', '', '', ''];
    public string $correct_answer = '';
    public int $points = 1;
    public int $order = 0;

    // Rich editor & attachments
    public $tempEditorImage = null;
    public $questionAttachmentFile = null;
    public ?string $currentQuestionAttachmentName = null;

    public function mount(int $examId): void
    {
        $this->exam = OnlineExam::with(['subject', 'classroom'])->findOrFail($examId);
    }

    public function rules(): array
    {
        $rules = [
            'question_type' => ['required', 'in:multiple_choice,essay,file_upload'],
            'question_text' => ['required', 'string'],
            'points' => ['required', 'integer', 'min:1'],
            'order' => ['integer', 'min:0'],
            'questionAttachmentFile' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ];

        if ($this->question_type === 'multiple_choice') {
            $rules['options'] = ['required', 'array', 'min:2'];
            $rules['options.*'] = ['required', 'string'];
            $rules['correct_answer'] = ['required', 'string'];
        }

        return $rules;
    }

    public function processEditorImage(): ?string
    {
        $this->validate([
            'tempEditorImage' => ['required', 'image', 'mimes:jpeg,png,jpg,webp,gif', 'max:2048'],
        ]);

        $path = $this->tempEditorImage->store('exam-images/' . $this->exam->id, 'public');
        $this->tempEditorImage = null;

        return Storage::disk('public')->url($path);
    }

    public function removeQuestionAttachment(): void
    {
        if ($this->editing && $this->editing->attachment_path) {
            Storage::disk('private')->delete($this->editing->attachment_path);
            $this->editing->update([
                'attachment_path' => null,
                'attachment_name' => null,
            ]);
            $this->currentQuestionAttachmentName = null;
        }
        $this->questionAttachmentFile = null;
    }

    public function createNew(): void
    {
        $this->reset(['question_type', 'question_text', 'options', 'correct_answer', 'points', 'editing', 'questionAttachmentFile', 'currentQuestionAttachmentName', 'tempEditorImage']);
        $this->options = ['', '', '', ''];
        $this->points = 1;
        $this->order = $this->exam->questions()->count();
        $this->questionModal = true;
    }

    public function edit(OnlineExamQuestion $question): void
    {
        $this->editing = $question;
        $this->question_type = $question->question_type;
        $this->question_text = $question->question_text;
        $this->options = $question->options ?? ['', '', '', ''];
        $this->correct_answer = $question->correct_answer ?? '';
        $this->points = $question->points;
        $this->order = $question->order;
        $this->questionAttachmentFile = null;
        $this->currentQuestionAttachmentName = $question->attachment_name;
        $this->tempEditorImage = null;
        $this->questionModal = true;
    }

    public function addOption(): void
    {
        $this->options[] = '';
    }

    public function removeOption(int $index): void
    {
        if (count($this->options) > 2) {
            array_splice($this->options, $index, 1);
            $this->options = array_values($this->options);
        }
    }

    public function save(): void
    {
        $validated = $this->validate();

        $sanitizedQuestionText = HtmlSanitizerService::clean($this->question_text);

        $sanitizedOptions = null;
        if ($this->question_type === 'multiple_choice') {
            $filtered = array_values(array_filter($this->options, fn ($o) => trim((string) $o) !== ''));
            $sanitizedOptions = array_map(fn ($o) => HtmlSanitizerService::clean((string) $o), $filtered);
        }

        $data = [
            'online_exam_id' => $this->exam->id,
            'question_type' => $this->question_type,
            'question_text' => $sanitizedQuestionText,
            'options' => $sanitizedOptions,
            'correct_answer' => $this->question_type === 'multiple_choice' ? $this->correct_answer : null,
            'points' => $this->points,
            'order' => $this->order,
        ];

        if ($this->questionAttachmentFile) {
            if ($this->editing && $this->editing->attachment_path) {
                Storage::disk('private')->delete($this->editing->attachment_path);
            }
            $storedPath = $this->questionAttachmentFile->store('exam-documents/questions', 'private');
            $data['attachment_path'] = $storedPath;
            $data['attachment_name'] = $this->questionAttachmentFile->getClientOriginalName();
        }

        if ($this->editing) {
            $this->editing->update($data);
        } else {
            OnlineExamQuestion::create($data);
        }

        $this->reset(['question_type', 'question_text', 'options', 'correct_answer', 'points', 'editing', 'questionAttachmentFile', 'currentQuestionAttachmentName', 'tempEditorImage']);
        $this->questionModal = false;
    }

    public function delete(OnlineExamQuestion $question): void
    {
        if ($question->attachment_path) {
            Storage::disk('private')->delete($question->attachment_path);
        }
        $question->delete();
    }

    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            OnlineExamQuestion::where('id', $id)->update(['order' => $index]);
        }
    }

    public function with(): array
    {
        return [
            'questions' => $this->exam->questions()->orderBy('order')->get(),
        ];
    }
}; ?>

<div class="p-6 space-y-6">
    <x-ui.header
        :title="__('Kelola Soal: ') . $exam->title"
        :subtitle="$exam->subject?->name . ' — ' . $exam->classroom?->name"
        separator
    >
        <x-slot:actions>
            <x-ui.button :label="__('Kembali')" icon="o-arrow-left" :link="route('admin.elearning.exams')" ghost />
            <x-ui.button :label="__('Tambah Soal')" icon="o-plus" class="btn-primary" wire:click="createNew" />
        </x-slot:actions>
    </x-ui.header>

    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-ui.card shadow>
            <div class="text-center">
                <div class="text-2xl font-bold text-emerald-600">{{ $questions->count() }}</div>
                <div class="text-sm text-slate-500">{{ __('Total Soal') }}</div>
            </div>
        </x-ui.card>
        <x-ui.card shadow>
            <div class="text-center">
                <div class="text-2xl font-bold text-blue-600">{{ $questions->sum('points') }}</div>
                <div class="text-sm text-slate-500">{{ __('Total Poin') }}</div>
            </div>
        </x-ui.card>
        <x-ui.card shadow>
            <div class="text-center">
                <div class="text-2xl font-bold text-amber-600">{{ $exam->duration_minutes ?? '∞' }}</div>
                <div class="text-sm text-slate-500">{{ __('Durasi (menit)') }}</div>
            </div>
        </x-ui.card>
    </div>

    {{-- Questions List --}}
    <div class="space-y-4">
        @forelse($questions as $index => $question)
            <x-ui.card shadow>
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-sm font-bold">
                                {{ $index + 1 }}
                            </span>
                            @php
                                $typeLabels = [
                                    'multiple_choice' => ['Pilihan Ganda', 'success'],
                                    'essay' => ['Essay', 'info'],
                                    'file_upload' => ['Upload File', 'amber'],
                                ];
                                $typeInfo = $typeLabels[$question->question_type] ?? ['Unknown', 'secondary'];
                            @endphp
                            <x-ui.badge :label="$typeInfo[0]" :variant="$typeInfo[1]" flat size="xs" />
                            <span class="text-xs text-slate-500">{{ $question->points }} {{ __('poin') }}</span>
                        </div>

                        <div class="text-slate-900 dark:text-white mb-3 prose dark:prose-invert max-w-none exam-content-render" x-init="$nextTick(() => window.renderMathInElement($el))">
                            {!! $question->question_text !!}
                        </div>

                        @if($question->attachment_path)
                            <div class="mb-3">
                                <a
                                    href="{{ route('elearning.questions.download-attachment', $question->id) }}"
                                    target="_blank"
                                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-medium text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700"
                                >
                                    <x-ui.icon name="o-paper-clip" class="w-4 h-4 text-emerald-600" />
                                    <span>{{ __('Lampiran Dokumen:') }} {{ $question->attachment_name ?: 'Download Soal' }}</span>
                                    <x-ui.icon name="o-arrow-down-tray" class="w-3.5 h-3.5 ml-1 text-slate-400" />
                                </a>
                            </div>
                        @endif

                        @if($question->question_type === 'multiple_choice' && $question->options)
                            <div class="space-y-1.5 ml-11">
                                @foreach($question->options as $optIndex => $option)
                                    <div class="flex items-start gap-2 text-sm {{ $question->correct_answer === chr(65 + $optIndex) ? 'text-emerald-600 dark:text-emerald-400 font-semibold' : 'text-slate-600 dark:text-slate-400' }}">
                                        <span class="w-6 h-6 flex items-center justify-center shrink-0 rounded-full border {{ $question->correct_answer === chr(65 + $optIndex) ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/30' : 'border-slate-300 dark:border-slate-600' }} text-xs font-medium mt-0.5">
                                            {{ chr(65 + $optIndex) }}
                                        </span>
                                        <div class="flex-1 exam-content-render" x-init="$nextTick(() => window.renderMathInElement($el))">
                                            {!! $option !!}
                                        </div>
                                        @if($question->correct_answer === chr(65 + $optIndex))
                                            <x-ui.icon name="o-check-circle" class="w-4 h-4 text-emerald-500 mt-1 shrink-0" />
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="flex gap-1 ml-4">
                        <x-ui.button icon="o-pencil-square" wire:click="edit({{ $question->id }})" ghost />
                        <x-ui.button
                            icon="o-trash"
                            class="text-red-600 dark:text-red-400"
                            wire:confirm="{{ __('Hapus soal ini?') }}"
                            wire:click="delete({{ $question->id }})"
                            ghost
                        />
                    </div>
                </div>
            </x-ui.card>
        @empty
            <x-ui.card shadow>
                <div class="text-center py-8 text-slate-500">
                    <x-ui.icon name="o-document-text" class="w-12 h-12 mx-auto mb-3 text-slate-300" />
                    <p>{{ __('Belum ada soal. Klik "Tambah Soal" untuk memulai.') }}</p>
                </div>
            </x-ui.card>
        @endforelse
    </div>

    {{-- Question Modal --}}
    <x-ui.modal wire:model="questionModal" persistent class="max-w-4xl">
        <x-ui.header :title="$editing ? __('Edit Soal') : __('Tambah Soal Baru')" separator />

        <form wire:submit="save" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-ui.select
                    wire:model.live="question_type"
                    :label="__('Tipe Soal')"
                    :options="[
                        ['id' => 'multiple_choice', 'name' => __('Pilihan Ganda')],
                        ['id' => 'essay', 'name' => __('Essay')],
                        ['id' => 'file_upload', 'name' => __('Upload File')],
                    ]"
                    option-label="name"
                    required
                />
                <x-ui.input wire:model="points" :label="__('Poin')" type="number" min="1" required />
                <x-ui.input wire:model="order" :label="__('Urutan')" type="number" min="0" />
            </div>

            {{-- Rich Editor untuk Pertanyaan --}}
            <div>
                <x-ui.rich-editor
                    :label="__('Pertanyaan (WYSIWYG: Gambar/Grafik & Rumus Matematika)')"
                    wire-model="question_text"
                    required
                />
            </div>

            {{-- Lampiran Berkas Butir Soal (Word / PDF) --}}
            <div class="p-3.5 rounded-xl border border-dashed border-slate-300 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/40 space-y-2">
                <div class="flex items-center justify-between">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                            {{ __('Lampiran Dokumen Tambahan Butir Soal (Word / PDF)') }}
                        </label>
                        <p class="text-[11px] text-slate-500">
                            {{ __('Opsional: Sisipkan dokumen studi kasus / teks bacaan (.pdf, .doc, .docx maks 10MB) untuk butir soal ini.') }}
                        </p>
                    </div>
                    @if($editing && $editing->attachment_path)
                        <a
                            href="{{ route('elearning.questions.download-attachment', $editing->id) }}"
                            target="_blank"
                            class="inline-flex items-center gap-1 text-xs text-emerald-600 hover:text-emerald-700 font-medium"
                        >
                            <x-ui.icon name="o-arrow-down-tray" class="w-3.5 h-3.5" />
                            {{ __('Unduh Lampiran') }}
                        </a>
                    @endif
                </div>

                @if($currentQuestionAttachmentName)
                    <div class="flex items-center justify-between p-2 rounded-lg bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 text-xs">
                        <span class="flex items-center gap-2 text-emerald-800 dark:text-emerald-300 font-medium truncate">
                            <x-ui.icon name="o-document-text" class="w-3.5 h-3.5 shrink-0 text-emerald-600" />
                            <span class="truncate">{{ $currentQuestionAttachmentName }}</span>
                        </span>
                        <x-ui.button
                            icon="o-trash"
                            class="text-red-500 hover:text-red-700 btn-xs"
                            ghost
                            wire:click="removeQuestionAttachment"
                            wire:confirm="{{ __('Hapus lampiran ini?') }}"
                        />
                    </div>
                @endif

                <input
                    type="file"
                    wire:model="questionAttachmentFile"
                    accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                    class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-500 file:text-white hover:file:bg-emerald-600 cursor-pointer"
                />

                <div wire:loading wire:target="questionAttachmentFile" class="text-xs text-emerald-600 flex items-center gap-1.5 font-medium">
                    <span class="animate-spin inline-block w-3.5 h-3.5 border-2 border-emerald-600 border-t-transparent rounded-full"></span>
                    {{ __('Mengunggah lampiran...') }}
                </div>
                @error('questionAttachmentFile') <span class="text-xs text-red-500 font-medium">{{ $message }}</span> @enderror
            </div>

            @if($question_type === 'multiple_choice')
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Opsi Jawaban (Dukungan Teks, Rumus LaTeX, & Gambar)') }}</label>
                        <span class="text-xs text-slate-500">{{ __('Minimal 2 opsi pilihan') }}</span>
                    </div>

                    @foreach($options as $index => $option)
                        <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/30 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="w-7 h-7 flex items-center justify-center shrink-0 rounded-full bg-emerald-100 dark:bg-emerald-900/50 text-xs font-bold text-emerald-800 dark:text-emerald-300">
                                    {{ chr(65 + $loop->index) }}
                                </span>
                                @if(count($options) > 2)
                                    <x-ui.button icon="o-x-mark" wire:click="removeOption({{ $loop->index }})" ghost class="text-red-500 btn-xs" />
                                @endif
                            </div>

                            <x-ui.rich-editor
                                wire-model="options.{{ $index }}"
                                :placeholder="__('Tulis opsi ') . chr(65 + $loop->index) . '...'"
                                min-height="70px"
                                compact
                            />
                        </div>
                    @endforeach

                    <x-ui.button :label="__('Tambah Opsi')" icon="o-plus" wire:click="addOption" ghost class="text-emerald-600 font-semibold" />

                    <x-ui.select
                        wire:model="correct_answer"
                        :label="__('Kunci Jawaban Benar')"
                        :options="collect($options)->values()->map(fn ($o, $i) => ['id' => chr(65 + $i), 'name' => chr(65 + $i) . '. ' . (strip_tags((string) $o) ?: 'Opsi ' . chr(65 + $i))])->toArray()"
                        option-label="name"
                        required
                    />
                </div>
            @endif

            @if($question_type === 'essay')
                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-sm text-blue-700 dark:text-blue-300">
                    <x-ui.icon name="o-information-circle" class="w-4 h-4 inline mr-1" />
                    {{ __('Soal essay akan dikoreksi secara manual oleh admin/guru.') }}
                </div>
            @endif

            @if($question_type === 'file_upload')
                <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg text-sm text-amber-700 dark:text-amber-300">
                    <x-ui.icon name="o-information-circle" class="w-4 h-4 inline mr-1" />
                    {{ __('Siswa akan mengupload file sebagai jawaban. Koreksi dilakukan manual.') }}
                </div>
            @endif

            <div class="flex justify-end gap-2 pt-4">
                <x-ui.button :label="__('Batal')" ghost @click="show = false" />
                <x-ui.button :label="__('Simpan')" type="submit" class="btn-primary" spinner="save" />
            </div>
        </form>
    </x-ui.modal>
</div>
