<?php

declare(strict_types=1);

use App\Models\OnlineExamAnswer;
use App\Models\OnlineExamSubmission;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public OnlineExamSubmission $submission;
    public array $grades = [];

    public function mount(int $submissionId): void
    {
        $this->submission = OnlineExamSubmission::with([
            'student', 'exam.subject', 'exam.classroom',
            'answers.question',
        ])->findOrFail($submissionId);

        // Pre-fill grades for essay/file_upload answers
        foreach ($this->submission->answers as $answer) {
            if (!$answer->question->isMultipleChoice()) {
                $this->grades[$answer->id] = [
                    'score' => $answer->score ?? '',
                    'feedback' => $answer->teacher_feedback ?? '',
                ];
            }
        }
    }

    public function saveGrade(int $answerId): void
    {
        $answer = OnlineExamAnswer::findOrFail($answerId);
        $gradeData = $this->grades[$answerId] ?? [];

        $score = is_numeric($gradeData['score'] ?? '') ? (float) $gradeData['score'] : null;
        $maxPoints = $answer->question->points;

        if ($score !== null && $score > $maxPoints) {
            $score = $maxPoints;
        }

        $answer->update([
            'score' => $score,
            'teacher_feedback' => $gradeData['feedback'] ?? null,
            'is_correct' => $score !== null ? $score >= ($maxPoints * 0.5) : null,
        ]);
    }

    public function finalizeGrading(): void
    {
        // Save all pending grades
        foreach ($this->grades as $answerId => $gradeData) {
            $this->saveGrade($answerId);
        }

        // Recalculate total score
        $this->submission->autoGrade();
        $this->submission->refresh();
    }

    public function with(): array
    {
        return [
            'answers' => $this->submission->answers()->with('question')->orderBy('id')->get(),
        ];
    }
}; ?>

@once
    @vite(['resources/js/rich-editor.js'])
@endonce

<div class="p-6 space-y-6">
    <x-ui.header
        :title="__('Detail Pengerjaan')"
        :subtitle="$submission->student?->name . ' — ' . $submission->exam?->title"
        separator
    >
        <x-slot:actions>
            <x-ui.button :label="__('Kembali')" icon="o-arrow-left" :link="route('admin.elearning.grade-recap')" ghost />
        </x-slot:actions>
    </x-ui.header>

    {{-- Submission Info --}}
    <x-ui.card shadow>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-center">
            <div>
                <div class="text-sm text-slate-500">{{ __('Siswa') }}</div>
                <div class="font-semibold text-slate-900 dark:text-white">{{ $submission->student?->name }}</div>
            </div>
            <div>
                <div class="text-sm text-slate-500">{{ __('Mata Pelajaran') }}</div>
                <div class="font-semibold">{{ $submission->exam?->subject?->name }}</div>
            </div>
            <div>
                <div class="text-sm text-slate-500">{{ __('Kelas') }}</div>
                <div class="font-semibold">{{ $submission->exam?->classroom?->name }}</div>
            </div>
            <div>
                <div class="text-sm text-slate-500">{{ __('Nilai') }}</div>
                @if($submission->total_score !== null)
                    @php $passed = $submission->total_score >= ($submission->exam?->passing_grade ?? 70); @endphp
                    <div class="text-2xl font-bold {{ $passed ? 'text-emerald-600' : 'text-red-600' }}">
                        {{ number_format($submission->total_score, 1) }}
                    </div>
                @else
                    <div class="text-2xl font-bold text-slate-400">-</div>
                @endif
            </div>
            <div>
                <div class="text-sm text-slate-500">{{ __('Status') }}</div>
                @php
                    $statusMap = ['in_progress' => ['Mengerjakan', 'amber'], 'submitted' => ['Disubmit', 'info'], 'graded' => ['Dinilai', 'success']];
                    $info = $statusMap[$submission->status] ?? ['Unknown', 'secondary'];
                @endphp
                <x-ui.badge :label="$info[0]" :variant="$info[1]" flat />
            </div>
        </div>
    </x-ui.card>

    {{-- Answers --}}
    <div class="space-y-4">
        @foreach($answers as $index => $answer)
            <x-ui.card shadow>
                <div class="space-y-3">
                    {{-- Question header --}}
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-sm font-bold">
                            {{ $index + 1 }}
                        </span>
                        @php
                            $typeLabels = ['multiple_choice' => 'Pilihan Ganda', 'essay' => 'Essay', 'file_upload' => 'Upload File'];
                        @endphp
                        <x-ui.badge :label="$typeLabels[$answer->question->question_type] ?? 'Unknown'" flat size="xs" />
                        <span class="text-xs text-slate-500">{{ $answer->question->points }} poin</span>

                        @if($answer->is_correct === true)
                            <x-ui.badge :label="__('Benar')" variant="success" flat size="xs" />
                        @elseif($answer->is_correct === false)
                            <x-ui.badge :label="__('Salah')" variant="danger" flat size="xs" />
                        @endif
                    </div>

                    {{-- Question text --}}
                    <div class="text-slate-900 dark:text-white font-medium prose dark:prose-invert max-w-none exam-content-render" x-init="$nextTick(() => window.renderMathInElement($el))">
                        {!! $answer->question->question_text !!}
                    </div>

                    @if($answer->question->attachment_path)
                        <div class="mb-2">
                            <a
                                href="{{ route('elearning.questions.download-attachment', $answer->question->id) }}"
                                target="_blank"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-medium text-slate-700 dark:text-slate-300"
                            >
                                <x-ui.icon name="o-paper-clip" class="w-3.5 h-3.5 text-emerald-600" />
                                <span>{{ __('Lampiran Dokumen Soal:') }} {{ $answer->question->attachment_name ?: 'Download' }}</span>
                                <x-ui.icon name="o-arrow-down-tray" class="w-3 h-3 text-slate-400" />
                            </a>
                        </div>
                    @endif

                    {{-- Student answer --}}
                    <div class="p-3 bg-slate-50 dark:bg-slate-800 rounded-lg">
                        <div class="text-xs font-medium text-slate-500 mb-1">{{ __('Jawaban Siswa:') }}</div>
                        @if($answer->question->isMultipleChoice())
                            <div class="text-slate-900 dark:text-white flex items-start gap-1">
                                <span class="font-semibold">{{ $answer->selected_option }}</span>
                                @if($answer->question->options && $answer->selected_option)
                                    @php $optIndex = ord($answer->selected_option) - 65; @endphp
                                    @if(isset($answer->question->options[$optIndex]))
                                        <div class="inline-block exam-content-render font-normal" x-init="$nextTick(() => window.renderMathInElement($el))">
                                            — {!! $answer->question->options[$optIndex] !!}
                                        </div>
                                    @endif
                                @endif
                            </div>
                            <div class="text-xs text-emerald-600 mt-1 flex items-start gap-1">
                                <span>{{ __('Jawaban benar:') }} {{ $answer->question->correct_answer }}</span>
                                @if($answer->question->options)
                                    @php $correctIndex = ord($answer->question->correct_answer) - 65; @endphp
                                    @if(isset($answer->question->options[$correctIndex]))
                                        <div class="inline-block exam-content-render" x-init="$nextTick(() => window.renderMathInElement($el))">
                                            — {!! $answer->question->options[$correctIndex] !!}
                                        </div>
                                    @endif
                                @endif
                            </div>
                        @elseif($answer->question->isFileUpload() && $answer->answer_file)
                            <a href="{{ Storage::url($answer->answer_file) }}" target="_blank" class="inline-flex items-center gap-1 text-emerald-600 hover:underline">
                                <x-ui.icon name="o-paper-clip" class="w-4 h-4" />
                                {{ __('Download File') }}
                            </a>
                        @else
                            <div class="text-slate-900 dark:text-white whitespace-pre-line">{{ $answer->answer_text ?? __('(Tidak dijawab)') }}</div>
                        @endif
                    </div>

                    {{-- Grading (for essay and file_upload) --}}
                    @if(!$answer->question->isMultipleChoice())
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/10 rounded-lg border border-emerald-200 dark:border-emerald-800">
                            <div class="text-xs font-medium text-emerald-700 dark:text-emerald-400 mb-2">{{ __('Penilaian Manual') }}</div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <x-ui.input
                                    wire:model="grades.{{ $answer->id }}.score"
                                    :label="__('Skor (max: ') . $answer->question->points . ')'"
                                    type="number"
                                    min="0"
                                    :max="$answer->question->points"
                                    step="0.5"
                                />
                                <x-ui.input
                                    wire:model="grades.{{ $answer->id }}.feedback"
                                    :label="__('Feedback (opsional)')"
                                    :placeholder="__('Catatan untuk siswa...')"
                                />
                            </div>
                            <div class="mt-2">
                                <x-ui.button
                                    :label="__('Simpan Nilai')"
                                    wire:click="saveGrade({{ $answer->id }})"
                                    class="btn-sm"
                                    ghost
                                    spinner="saveGrade"
                                />
                            </div>
                        </div>
                    @endif

                    {{-- Existing feedback display --}}
                    @if($answer->teacher_feedback)
                        <div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded text-sm text-blue-700 dark:text-blue-300">
                            <strong>{{ __('Feedback:') }}</strong> {{ $answer->teacher_feedback }}
                        </div>
                    @endif
                </div>
            </x-ui.card>
        @endforeach
    </div>

    {{-- Finalize Button --}}
    @if($submission->status !== 'graded')
        <div class="flex justify-end">
            <x-ui.button
                :label="__('Finalisasi Penilaian')"
                icon="o-check-circle"
                class="btn-primary"
                wire:click="finalizeGrading"
                wire:confirm="{{ __('Finalisasi penilaian? Skor total akan dihitung ulang.') }}"
                spinner="finalizeGrading"
            />
        </div>
    @endif
</div>
