<?php

declare(strict_types=1);

use App\Models\OnlineExamSubmission;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.student')] class extends Component {
    public OnlineExamSubmission $submission;

    public function mount(int $submissionId): void
    {
        $this->submission = OnlineExamSubmission::where('student_id', auth()->id())
            ->with(['exam.subject', 'exam.classroom', 'answers.question'])
            ->findOrFail($submissionId);
    }
}; ?>

@once
    @vite(['resources/js/rich-editor.js'])
@endonce

<div class="p-6 space-y-6">
    <x-ui.header
        :title="__('Hasil Ulangan')"
        :subtitle="$submission->exam?->title"
        separator
    >
        <x-slot:actions>
            <x-ui.button :label="__('Kembali')" icon="o-arrow-left" :link="route('student.exams')" ghost />
        </x-slot:actions>
    </x-ui.header>

    {{-- Result Summary --}}
    <x-ui.card shadow>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
            <div>
                <div class="text-sm text-slate-500 mb-1">{{ __('Nilai') }}</div>
                @if($submission->total_score !== null)
                    @php
                        $passing = $submission->exam?->passing_grade ?? 70;
                        $passed = $submission->total_score >= $passing;
                    @endphp
                    <div class="text-4xl font-bold {{ $passed ? 'text-emerald-600' : 'text-red-600' }}">
                        {{ number_format($submission->total_score, 1) }}
                    </div>
                    <x-ui.badge :label="$passed ? __('LULUS') : __('BELUM LULUS')" :variant="$passed ? 'success' : 'danger'" flat />
                @else
                    <div class="text-4xl font-bold text-slate-400">-</div>
                    <x-ui.badge :label="__('Belum Dinilai')" variant="amber" flat />
                @endif
            </div>
            <div>
                <div class="text-sm text-slate-500 mb-1">{{ __('KKM') }}</div>
                <div class="text-2xl font-bold text-slate-700 dark:text-white">{{ $submission->exam?->passing_grade }}</div>
            </div>
            <div>
                <div class="text-sm text-slate-500 mb-1">{{ __('Waktu Submit') }}</div>
                <div class="text-sm font-medium text-slate-700 dark:text-white">
                    {{ $submission->submitted_at?->format('d M Y') }}<br>
                    {{ $submission->submitted_at?->format('H:i') }}
                </div>
            </div>
            <div>
                <div class="text-sm text-slate-500 mb-1">{{ __('Status') }}</div>
                @php
                    $statusMap = ['submitted' => ['Menunggu Penilaian', 'info'], 'graded' => ['Selesai Dinilai', 'success']];
                    $info = $statusMap[$submission->status] ?? ['Unknown', 'secondary'];
                @endphp
                <x-ui.badge :label="$info[0]" :variant="$info[1]" flat />
            </div>
        </div>
    </x-ui.card>

    {{-- Answer Details --}}
    <div class="space-y-4">
        @foreach($submission->answers->sortBy('question.order') as $index => $answer)
            <x-ui.card shadow>
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full {{ $answer->is_correct ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700' : ($answer->is_correct === false ? 'bg-red-100 dark:bg-red-900/30 text-red-700' : 'bg-slate-100 dark:bg-slate-700 text-slate-600') }} text-sm font-bold">
                            {{ $index + 1 }}
                        </span>
                        @if($answer->is_correct === true)
                            <x-ui.badge :label="__('Benar')" variant="success" flat size="xs" />
                        @elseif($answer->is_correct === false)
                            <x-ui.badge :label="__('Salah')" variant="danger" flat size="xs" />
                        @else
                            <x-ui.badge :label="__('Belum Dinilai')" variant="amber" flat size="xs" />
                        @endif
                        @if($answer->score !== null)
                            <span class="text-sm text-slate-500">{{ $answer->score }}/{{ $answer->question->points }} poin</span>
                        @endif
                    </div>

                    <div class="text-slate-900 dark:text-white font-medium prose dark:prose-invert max-w-none exam-content-render" x-init="$nextTick(() => window.renderMathInElement($el))">
                        {!! $answer->question->question_text !!}
                    </div>

                    <div class="p-3 bg-slate-50 dark:bg-slate-800 rounded-lg">
                        <div class="text-xs font-medium text-slate-500 mb-1">{{ __('Jawaban Anda:') }}</div>
                        @if($answer->question->isMultipleChoice())
                            <div class="font-semibold flex items-start gap-1 {{ $answer->is_correct ? 'text-emerald-600' : 'text-red-600' }}">
                                <span>{{ $answer->selected_option }}</span>
                                @if($answer->question->options && $answer->selected_option)
                                    @php
                                        $selectedText = $answer->question->options[$answer->selected_option] 
                                            ?? (isset($answer->question->options[ord($answer->selected_option) - 65]) ? $answer->question->options[ord($answer->selected_option) - 65] : null);
                                    @endphp
                                    @if($selectedText)
                                        <div class="inline-block exam-content-render font-normal" x-init="$nextTick(() => window.renderMathInElement($el))">
                                            — {!! $selectedText !!}
                                        </div>
                                    @endif
                                @endif
                            </div>
                            @if($answer->is_correct === false)
                                <div class="text-sm text-emerald-600 mt-1 flex items-start gap-1">
                                    <span>{{ __('Jawaban benar:') }} {{ $answer->question->correct_answer }}</span>
                                    @if($answer->question->options && $answer->question->correct_answer)
                                        @php
                                            $correctText = $answer->question->options[$answer->question->correct_answer] 
                                                ?? (isset($answer->question->options[ord($answer->question->correct_answer) - 65]) ? $answer->question->options[ord($answer->question->correct_answer) - 65] : null);
                                        @endphp
                                        @if($correctText)
                                            <div class="inline-block exam-content-render" x-init="$nextTick(() => window.renderMathInElement($el))">
                                                — {!! $correctText !!}
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            @endif
                        @elseif($answer->answer_file)
                            <span class="text-sm text-slate-600">{{ __('File telah diupload') }}</span>
                        @else
                            <div class="text-slate-700 dark:text-slate-300 whitespace-pre-line">{{ $answer->answer_text ?? __('(Tidak dijawab)') }}</div>
                        @endif
                    </div>

                    @if($answer->teacher_feedback)
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <div class="text-xs font-medium text-blue-700 dark:text-blue-400 mb-1">{{ __('Feedback Guru:') }}</div>
                            <p class="text-sm text-blue-800 dark:text-blue-300">{{ $answer->teacher_feedback }}</p>
                        </div>
                    @endif
                </div>
            </x-ui.card>
        @endforeach
    </div>
</div>
