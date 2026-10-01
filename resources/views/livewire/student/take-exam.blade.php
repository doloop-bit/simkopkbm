<?php

declare(strict_types=1);

use App\Models\OnlineExam;
use App\Models\OnlineExamAnswer;
use App\Models\OnlineExamSubmission;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.student')] class extends Component {
    use WithFileUploads;

    public OnlineExam $exam;
    public OnlineExamSubmission $submission;
    public array $answers = [];
    public $uploadedFiles = [];
    public bool $confirmSubmitModal = false;

    public function mount(int $examId): void
    {
        $this->exam = OnlineExam::where('is_published', true)
            ->with('questions')
            ->findOrFail($examId);

        $studentId = auth()->id();

        // Find or create submission
        $this->submission = OnlineExamSubmission::firstOrCreate(
            ['online_exam_id' => $this->exam->id, 'student_id' => $studentId],
            ['started_at' => now(), 'status' => 'in_progress']
        );

        // If already submitted, redirect to results
        if ($this->submission->status !== 'in_progress') {
            $this->redirect(route('student.exam-result', $this->submission->id));
            return;
        }

        // Load existing answers
        $existingAnswers = OnlineExamAnswer::where('submission_id', $this->submission->id)
            ->pluck('selected_option', 'question_id')
            ->toArray();

        $existingTextAnswers = OnlineExamAnswer::where('submission_id', $this->submission->id)
            ->pluck('answer_text', 'question_id')
            ->toArray();

        $questions = $this->exam->shuffle_questions
            ? $this->exam->questions->shuffle()
            : $this->exam->questions;

        foreach ($questions as $question) {
            $this->answers[$question->id] = [
                'selected_option' => $existingAnswers[$question->id] ?? '',
                'answer_text' => $existingTextAnswers[$question->id] ?? '',
            ];
        }
    }

    public function saveAnswer(int $questionId): void
    {
        $answerData = $this->answers[$questionId] ?? [];

        OnlineExamAnswer::updateOrCreate(
            ['submission_id' => $this->submission->id, 'question_id' => $questionId],
            [
                'selected_option' => $answerData['selected_option'] ?? null,
                'answer_text' => $answerData['answer_text'] ?? null,
            ]
        );
    }

    public function uploadAnswerFile(int $questionId): void
    {
        if (isset($this->uploadedFiles[$questionId])) {
            $file = $this->uploadedFiles[$questionId];
            $path = $file->store('elearning/answers/' . $this->submission->id, 'public');

            OnlineExamAnswer::updateOrCreate(
                ['submission_id' => $this->submission->id, 'question_id' => $questionId],
                ['answer_file' => $path]
            );

            unset($this->uploadedFiles[$questionId]);
        }
    }

    public function confirmSubmit(): void
    {
        $this->confirmSubmitModal = true;
    }

    public function submitExam(): void
    {
        // Save all pending answers
        foreach ($this->answers as $questionId => $answerData) {
            $this->saveAnswer($questionId);
        }

        // Update submission
        $this->submission->update([
            'submitted_at' => now(),
            'status' => 'submitted',
        ]);

        // Auto-grade
        $this->submission->autoGrade();

        $this->redirect(route('student.exam-result', $this->submission->id));
    }

    public function with(): array
    {
        $questions = $this->exam->shuffle_questions
            ? $this->exam->questions->shuffle()
            : $this->exam->questions;

        $answeredCount = OnlineExamAnswer::where('submission_id', $this->submission->id)
            ->whereNotNull('selected_option')
            ->orWhereNotNull('answer_text')
            ->orWhereNotNull('answer_file')
            ->where('submission_id', $this->submission->id)
            ->count();

        return [
            'questions' => $questions,
            'answeredCount' => $answeredCount,
            'totalQuestions' => $questions->count(),
        ];
    }
}; ?>

<div class="p-6 space-y-6" x-data="{
    timeLeft: {{ $exam->duration_minutes ? $exam->duration_minutes * 60 : 0 }},
    startTime: {{ $submission->started_at->timestamp ?? 'null' }},
    hasDuration: {{ $exam->duration_minutes ? 'true' : 'false' }},
    timerInterval: null,
    formattedTime: '',
    init() {
        if (this.hasDuration && this.startTime) {
            const elapsed = Math.floor(Date.now() / 1000) - this.startTime;
            this.timeLeft = Math.max(0, ({{ $exam->duration_minutes ?? 0 }} * 60) - elapsed);
            this.updateTimer();
            this.timerInterval = setInterval(() => {
                this.timeLeft--;
                this.updateTimer();
                if (this.timeLeft <= 0) {
                    clearInterval(this.timerInterval);
                    $wire.submitExam();
                }
            }, 1000);
        }
    },
    updateTimer() {
        const h = Math.floor(this.timeLeft / 3600);
        const m = Math.floor((this.timeLeft % 3600) / 60);
        const s = this.timeLeft % 60;
        this.formattedTime = (h > 0 ? h + ':' : '') + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
    }
}">

    {{-- Header with Timer --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $exam->title }}</h1>
            <p class="text-sm text-slate-500">{{ $exam->subject?->name }} — {{ $exam->classroom?->name }}</p>
        </div>
        <div class="flex items-center gap-4">
            @if($exam->duration_minutes)
                <div class="px-4 py-2 bg-slate-900 dark:bg-slate-700 rounded-lg text-white font-mono text-xl"
                     :class="timeLeft < 300 ? 'bg-red-600 dark:bg-red-700 animate-pulse' : ''">
                    <x-ui.icon name="o-clock" class="w-5 h-5 inline mr-1" />
                    <span x-text="formattedTime"></span>
                </div>
            @endif
            <div class="text-sm text-slate-500">
                {{ $answeredCount }}/{{ $totalQuestions }} {{ __('dijawab') }}
            </div>
        </div>
    </div>

    @if($exam->description)
        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-sm text-blue-700 dark:text-blue-300">
            <x-ui.icon name="o-information-circle" class="w-4 h-4 inline mr-1" />
            {{ $exam->description }}
        </div>
    @endif

    {{-- Questions --}}
    <div class="space-y-6">
        @foreach($questions as $index => $question)
            <x-ui.card shadow>
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-sm font-bold">
                            {{ $index + 1 }}
                        </span>
                        <span class="text-xs text-slate-500">{{ $question->points }} poin</span>
                        @php
                            $typeLabels = ['multiple_choice' => 'Pilihan Ganda', 'essay' => 'Essay', 'file_upload' => 'Upload File'];
                        @endphp
                        <x-ui.badge :label="$typeLabels[$question->question_type]" flat size="xs" />
                    </div>

                    <div class="text-slate-900 dark:text-white font-medium whitespace-pre-line">{{ $question->question_text }}</div>

                    @if($question->question_type === 'multiple_choice' && $question->options)
                        <div class="space-y-2">
                            @foreach($question->options as $optIndex => $option)
                                @php
                                    $letter = is_numeric($optIndex) ? chr(65 + (int) $optIndex) : (string) $optIndex;
                                @endphp
                                <label class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-colors
                                    {{ ($answers[$question->id]['selected_option'] ?? '') === $letter
                                        ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20'
                                        : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                                    <input type="radio"
                                        wire:model="answers.{{ $question->id }}.selected_option"
                                        wire:change="saveAnswer({{ $question->id }})"
                                        value="{{ $letter }}"
                                        class="text-emerald-600 focus:ring-emerald-500"
                                    />
                                    <span class="w-6 h-6 flex items-center justify-center rounded-full bg-slate-100 dark:bg-slate-700 text-xs font-medium">
                                        {{ $letter }}
                                    </span>
                                    <span class="text-slate-700 dark:text-slate-300">{{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                    @elseif($question->question_type === 'essay')
                        <textarea
                            wire:model="answers.{{ $question->id }}.answer_text"
                            wire:change="saveAnswer({{ $question->id }})"
                            rows="5"
                            class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white focus:border-emerald-500 focus:ring-emerald-500"
                            placeholder="{{ __('Tulis jawaban Anda di sini...') }}"
                        ></textarea>
                    @elseif($question->question_type === 'file_upload')
                        <div>
                            <input type="file"
                                wire:model="uploadedFiles.{{ $question->id }}"
                                class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100"
                            />
                            <div wire:loading wire:target="uploadedFiles.{{ $question->id }}" class="text-sm text-emerald-600 mt-1">
                                {{ __('Mengupload...') }}
                            </div>
                            @if(isset($uploadedFiles[$question->id]))
                                <x-ui.button :label="__('Simpan File')" wire:click="uploadAnswerFile({{ $question->id }})" class="btn-sm mt-2" ghost />
                            @endif
                        </div>
                    @endif
                </div>
            </x-ui.card>
        @endforeach
    </div>

    {{-- Submit Button --}}
    <div class="flex justify-end sticky bottom-4">
        <x-ui.button
            :label="__('Submit Ulangan')"
            icon="o-paper-airplane"
            class="btn-primary shadow-lg"
            wire:click="confirmSubmit"
        />
    </div>

    {{-- Confirm Submit Modal --}}
    <x-ui.modal wire:model="confirmSubmitModal">
        <x-ui.header :title="__('Konfirmasi Submit')" separator />
        <div class="space-y-4">
            <p class="text-slate-700 dark:text-slate-300">
                {{ __('Anda telah menjawab') }} <strong>{{ $answeredCount }}</strong> {{ __('dari') }} <strong>{{ $totalQuestions }}</strong> {{ __('soal.') }}
            </p>
            @if($answeredCount < $totalQuestions)
                <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg text-sm text-amber-700 dark:text-amber-300">
                    <x-ui.icon name="o-exclamation-triangle" class="w-4 h-4 inline mr-1" />
                    {{ __('Masih ada soal yang belum dijawab!') }}
                </div>
            @endif
            <p class="text-sm text-slate-500">{{ __('Setelah submit, Anda tidak bisa mengubah jawaban.') }}</p>
            <div class="flex justify-end gap-2 pt-4">
                <x-ui.button :label="__('Kembali')" ghost @click="show = false" />
                <x-ui.button :label="__('Ya, Submit')" class="btn-primary" wire:click="submitExam" spinner="submitExam" />
            </div>
        </div>
    </x-ui.modal>
</div>
