<?php

declare(strict_types=1);

use App\Models\OnlineExamSubmission;
use App\Models\OnlineMaterial;
use App\Models\OnlineExam;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.student')] class extends Component {
    public function with(): array
    {
        $studentId = auth()->id();

        $student = auth()->user();
        $classroomId = $student->latestProfile?->profileable?->classroom_id;

        $recentMaterials = OnlineMaterial::query()
            ->where('is_published', true)
            ->when($classroomId, fn ($q) => $q->where('classroom_id', $classroomId))
            ->with(['subject', 'classroom'])
            ->latest('published_at')
            ->limit(5)
            ->get();

        $activeExams = OnlineExam::query()
            ->where('is_published', true)
            ->when($classroomId, fn ($q) => $q->where('classroom_id', $classroomId))
            ->where(function ($q) {
                $q->whereNull('start_time')->orWhere('start_time', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_time')->orWhere('end_time', '>=', now());
            })
            ->withCount('questions')
            ->with(['subject', 'classroom'])
            ->latest()
            ->limit(5)
            ->get();

        $recentSubmissions = OnlineExamSubmission::query()
            ->where('student_id', $studentId)
            ->with(['exam.subject'])
            ->latest('submitted_at')
            ->limit(5)
            ->get();

        $totalExams = OnlineExamSubmission::where('student_id', $studentId)->count();
        $avgScore = OnlineExamSubmission::where('student_id', $studentId)
            ->where('status', 'graded')
            ->avg('total_score');

        return [
            'recentMaterials' => $recentMaterials,
            'activeExams' => $activeExams,
            'recentSubmissions' => $recentSubmissions,
            'totalExams' => $totalExams,
            'avgScore' => round($avgScore ?? 0, 1),
        ];
    }
}; ?>

<div class="p-6 space-y-6">
    <x-ui.header :title="__('Dashboard Siswa')" :subtitle="__('Selamat datang, ') . auth()->user()->name" separator />

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-ui.card shadow>
            <div class="text-center">
                <div class="text-3xl font-bold text-emerald-600">{{ $totalExams }}</div>
                <div class="text-sm text-slate-500 mt-1">{{ __('Ulangan Dikerjakan') }}</div>
            </div>
        </x-ui.card>
        <x-ui.card shadow>
            <div class="text-center">
                <div class="text-3xl font-bold text-blue-600">{{ $avgScore }}</div>
                <div class="text-sm text-slate-500 mt-1">{{ __('Rata-rata Nilai') }}</div>
            </div>
        </x-ui.card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Recent Materials --}}
        <x-ui.card shadow :title="__('Materi Terbaru')">
            <div class="space-y-3">
                @forelse($recentMaterials as $material)
                    <a href="{{ route('student.material-detail', $material->id) }}" class="block p-3 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors" wire:navigate>
                        <div class="font-medium text-slate-900 dark:text-white">{{ $material->title }}</div>
                        <div class="text-sm text-slate-500">{{ $material->subject?->name }} — {{ $material->classroom?->name }}</div>
                    </a>
                @empty
                    <p class="text-sm text-slate-500 text-center py-4">{{ __('Belum ada materi.') }}</p>
                @endforelse
            </div>
            <div class="mt-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                <a href="{{ route('student.materials') }}" class="text-sm text-emerald-600 hover:underline" wire:navigate>{{ __('Lihat semua materi →') }}</a>
            </div>
        </x-ui.card>

        {{-- Active Exams --}}
        <x-ui.card shadow :title="__('Ulangan Aktif')">
            <div class="space-y-3">
                @forelse($activeExams as $exam)
                    <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800">
                        <div class="font-medium text-slate-900 dark:text-white">{{ $exam->title }}</div>
                        <div class="text-sm text-slate-500">{{ $exam->subject?->name }} — {{ $exam->questions_count }} soal</div>
                        @if($exam->end_time)
                            <div class="text-xs text-amber-600 mt-1">{{ __('Batas:') }} {{ $exam->end_time->format('d M Y H:i') }}</div>
                        @endif
                        <div class="mt-2">
                            <x-ui.button :label="__('Kerjakan')" :link="route('student.take-exam', $exam->id)" class="btn-primary btn-sm" icon="o-play" />
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 text-center py-4">{{ __('Tidak ada ulangan aktif.') }}</p>
                @endforelse
            </div>
            <div class="mt-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                <a href="{{ route('student.exams') }}" class="text-sm text-emerald-600 hover:underline" wire:navigate>{{ __('Lihat semua ulangan →') }}</a>
            </div>
        </x-ui.card>
    </div>

    {{-- Recent Submissions --}}
    @if($recentSubmissions->isNotEmpty())
        <x-ui.card shadow :title="__('Riwayat Pengerjaan')" padding="false">
            <x-ui.table
                :headers="[
                    ['key' => 'exam', 'label' => __('Ulangan')],
                    ['key' => 'score', 'label' => __('Nilai')],
                    ['key' => 'status', 'label' => __('Status')],
                    ['key' => 'submitted_at', 'label' => __('Waktu')],
                ]"
                :rows="$recentSubmissions"
            >
                @scope('cell_exam', $submission)
                    <span class="font-medium">{{ $submission->exam?->title }}</span>
                @endscope

                @scope('cell_score', $submission)
                    @if($submission->total_score !== null)
                        <span class="font-bold {{ $submission->total_score >= 70 ? 'text-emerald-600' : 'text-red-600' }}">
                            {{ number_format($submission->total_score, 1) }}
                        </span>
                    @else
                        <span class="text-slate-400">-</span>
                    @endif
                @endscope

                @scope('cell_status', $submission)
                    @php
                        $statusMap = ['in_progress' => ['Mengerjakan', 'amber'], 'submitted' => ['Disubmit', 'info'], 'graded' => ['Dinilai', 'success']];
                        $info = $statusMap[$submission->status] ?? ['Unknown', 'secondary'];
                    @endphp
                    <x-ui.badge :label="$info[0]" :variant="$info[1]" flat size="xs" />
                @endscope

                @scope('cell_submitted_at', $submission)
                    <span class="text-sm text-slate-500">{{ $submission->submitted_at?->format('d M Y H:i') ?? '-' }}</span>
                @endscope
            </x-ui.table>
        </x-ui.card>
    @endif
</div>
