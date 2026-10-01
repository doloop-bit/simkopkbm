<?php

declare(strict_types=1);

use App\Models\OnlineExam;
use App\Models\OnlineExamSubmission;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.student')] class extends Component {
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $student = auth()->user();
        $studentId = $student->id;
        $classroomId = $student->latestProfile?->profileable?->classroom_id;

        $exams = OnlineExam::query()
            ->where('is_published', true)
            ->when($classroomId, fn ($q) => $q->where('classroom_id', $classroomId))
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->withCount('questions')
            ->with(['subject', 'classroom'])
            ->latest()
            ->paginate(10);

        // Get submission status for each exam
        $submissionMap = OnlineExamSubmission::where('student_id', $studentId)
            ->whereIn('online_exam_id', $exams->pluck('id'))
            ->get()
            ->keyBy('online_exam_id');

        return [
            'exams' => $exams,
            'submissionMap' => $submissionMap,
        ];
    }
}; ?>

<div class="p-6 space-y-6">
    <x-ui.header :title="__('Ulangan Online')" :subtitle="__('Daftar ulangan yang tersedia untuk Anda.')" separator />

    <x-ui.input wire:model.live.debounce.300ms="search" :placeholder="__('Cari ulangan...')" icon="o-magnifying-glass" />

    <div class="space-y-4">
        @forelse($exams as $exam)
            @php
                $submission = $submissionMap[$exam->id] ?? null;
                $isActive = $exam->isActive();
                $hasStarted = $submission !== null;
                $isCompleted = $submission && in_array($submission->status, ['submitted', 'graded']);
            @endphp
            <x-ui.card shadow>
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="font-semibold text-slate-900 dark:text-white">{{ $exam->title }}</h3>
                            @php
                                $types = ['daily' => 'Harian', 'midterm' => 'UTS', 'final' => 'UAS'];
                            @endphp
                            <x-ui.badge :label="$types[$exam->exam_type] ?? $exam->exam_type" flat size="xs" />
                        </div>
                        <div class="text-sm text-slate-500 space-y-1">
                            <div>{{ $exam->subject?->name }} — {{ $exam->classroom?->name }}</div>
                            <div class="flex flex-wrap gap-3">
                                <span class="flex items-center gap-1">
                                    <x-ui.icon name="o-document-text" class="w-4 h-4" />
                                    {{ $exam->questions_count }} soal
                                </span>
                                @if($exam->duration_minutes)
                                    <span class="flex items-center gap-1">
                                        <x-ui.icon name="o-clock" class="w-4 h-4" />
                                        {{ $exam->duration_minutes }} menit
                                    </span>
                                @endif
                                <span>KKM: {{ $exam->passing_grade }}</span>
                            </div>
                            @if($exam->start_time || $exam->end_time)
                                <div class="text-xs text-amber-600">
                                    @if($exam->start_time)
                                        {{ __('Mulai:') }} {{ $exam->start_time->format('d M Y H:i') }}
                                    @endif
                                    @if($exam->end_time)
                                        — {{ __('Batas:') }} {{ $exam->end_time->format('d M Y H:i') }}
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        @if($isCompleted)
                            <div class="text-right mr-3">
                                @if($submission->total_score !== null)
                                    <div class="text-2xl font-bold {{ $submission->total_score >= $exam->passing_grade ? 'text-emerald-600' : 'text-red-600' }}">
                                        {{ number_format($submission->total_score, 1) }}
                                    </div>
                                @endif
                                @php
                                    $statusMap = ['submitted' => ['Disubmit', 'info'], 'graded' => ['Dinilai', 'success']];
                                    $info = $statusMap[$submission->status] ?? ['', ''];
                                @endphp
                                <x-ui.badge :label="$info[0]" :variant="$info[1]" flat size="xs" />
                            </div>
                            <x-ui.button :label="__('Lihat Hasil')" :link="route('student.exam-result', $submission->id)" ghost icon="o-eye" />
                        @elseif($hasStarted && $submission->isInProgress())
                            <x-ui.button :label="__('Lanjutkan')" :link="route('student.take-exam', $exam->id)" class="btn-primary" icon="o-play" />
                        @elseif($isActive)
                            <x-ui.button :label="__('Kerjakan')" :link="route('student.take-exam', $exam->id)" class="btn-primary" icon="o-play" />
                        @else
                            <x-ui.badge :label="__('Belum Dibuka')" variant="secondary" flat />
                        @endif
                    </div>
                </div>
            </x-ui.card>
        @empty
            <x-ui.card shadow>
                <div class="text-center py-12 text-slate-500">
                    <x-ui.icon name="o-clipboard-document-check" class="w-12 h-12 mx-auto mb-3 text-slate-300" />
                    <p>{{ __('Belum ada ulangan tersedia.') }}</p>
                </div>
            </x-ui.card>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $exams->links() }}
    </div>
</div>
