<?php

declare(strict_types=1);

use App\Models\AcademicYear;
use App\Models\OnlineExam;
use App\Models\OnlineExamSubmission;
use App\Models\OnlineMaterial;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.student')] class extends Component {
    public function with(): array
    {
        $student = auth()->user();
        $studentId = $student->id;

        $studentProfile = $student->studentProfile ?? $student->latestProfile?->profileable;
        $classroom = $studentProfile?->classroom;
        $classroomId = $classroom?->id;
        $level = $classroom?->level;

        $activeYear = $classroom?->academicYear ?? AcademicYear::where('is_active', true)->first();

        // 1. Total available materials for student
        $totalMaterials = OnlineMaterial::query()
            ->where('is_published', true)
            ->when($classroomId, fn ($q) => $q->where('classroom_id', $classroomId))
            ->count();

        // 2. Recent materials
        $recentMaterials = OnlineMaterial::query()
            ->where('is_published', true)
            ->when($classroomId, fn ($q) => $q->where('classroom_id', $classroomId))
            ->with(['subject', 'classroom'])
            ->latest('published_at')
            ->limit(4)
            ->get();

        // 3. Active exams query
        $activeExamsQuery = OnlineExam::query()
            ->where('is_published', true)
            ->when($classroomId, fn ($q) => $q->where('classroom_id', $classroomId))
            ->where(function ($q) {
                $q->whereNull('start_time')->orWhere('start_time', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_time')->orWhere('end_time', '>=', now());
            });

        $activeExamsCount = (clone $activeExamsQuery)->count();

        $activeExams = $activeExamsQuery
            ->withCount('questions')
            ->with(['subject', 'classroom'])
            ->latest()
            ->limit(3)
            ->get();

        // 4. Submissions and exam history
        $submissions = OnlineExamSubmission::query()
            ->where('student_id', $studentId)
            ->with(['exam.subject'])
            ->latest('submitted_at')
            ->get();

        $totalExamsDone = $submissions->count();
        $gradedSubmissions = $submissions->where('status', 'graded');
        $avgScore = $gradedSubmissions->avg('total_score');
        $passedCount = $gradedSubmissions->filter(fn ($s) => (float) $s->total_score >= ($s->exam?->passing_grade ?? 70))->count();
        $recentSubmissions = $submissions->take(5);

        return [
            'student' => $student,
            'studentProfile' => $studentProfile,
            'classroom' => $classroom,
            'level' => $level,
            'academicYear' => $activeYear,
            'totalMaterials' => $totalMaterials,
            'recentMaterials' => $recentMaterials,
            'activeExamsCount' => $activeExamsCount,
            'activeExams' => $activeExams,
            'totalExamsDone' => $totalExamsDone,
            'avgScore' => round($avgScore ?? 0, 1),
            'passedCount' => $passedCount,
            'recentSubmissions' => $recentSubmissions,
        ];
    }
}; ?>

<div class="p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl mx-auto">
    {{-- Header Identitas Siswa --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-emerald-600 dark:bg-emerald-500 text-white font-bold text-2xl flex items-center justify-center shrink-0 shadow-sm">
                {{ strtoupper(substr($student->name, 0, 1)) }}
            </div>
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
                        {{ __('Siswa Daring Aktif') }}
                    </span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">
                        {{ $academicYear?->name ?? 'Tahun Ajaran Aktif' }}
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">
                    {{ $student->name }}
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                    NIS: <strong class="text-slate-700 dark:text-slate-300">{{ $studentProfile?->nis ?? '-' }}</strong> • 
                    Kelas: <strong class="text-slate-700 dark:text-slate-300">{{ $classroom?->name ?? ($level?->name ?? 'Daring') }}</strong>
                </p>
            </div>
        </div>
        <div class="flex items-center gap-3 pt-3 md:pt-0 border-t md:border-t-0 border-slate-100 dark:border-slate-700">
            <a href="{{ route('profile.edit') }}" class="btn btn-outline btn-sm gap-2" wire:navigate>
                <x-ui.icon name="o-user-circle" class="w-4 h-4" />
                {{ __('Profil Saya') }}
            </a>
        </div>
    </div>

    {{-- SECTION: BLOK AKADEMIK (4 Ubin Sesuai Fitur Riil) --}}
    <div class="space-y-3">
        <div class="flex items-center gap-2">
            <div class="w-1.5 h-5 bg-blue-700 rounded-full"></div>
            <h2 class="text-sm font-extrabold tracking-wider text-slate-800 dark:text-slate-200 uppercase">
                {{ __('BLOK AKADEMIK') }}
            </h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            
            {{-- Ubin 1: Materi Pembelajaran (Pastel Green) --}}
            <a href="{{ route('student.materials') }}" wire:navigate
               class="group flex flex-col items-center justify-center text-center p-6 rounded-2xl bg-[#ecf7ed] hover:bg-[#e1f3e2] border border-[#c8e6c9] dark:bg-emerald-950/40 dark:hover:bg-emerald-900/50 dark:border-emerald-800/50 transition-all duration-200 shadow-sm hover:shadow-md cursor-pointer">
                <div class="w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-900/60 flex items-center justify-center text-[#1b5e20] dark:text-emerald-300 mb-3 group-hover:scale-105 transition-transform">
                    <x-ui.icon name="o-book-open" class="w-6 h-6" />
                </div>
                <span class="text-base font-bold text-[#1b5e20] dark:text-emerald-200 tracking-tight">
                    {{ __('Materi Pelajaran') }}
                </span>
                <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-400 mt-1">
                    {{ $totalMaterials }} {{ __('Modul Tersedia') }}
                </span>
            </a>

            {{-- Ubin 2: Ulangan & Ujian (Pastel Rose) --}}
            <a href="{{ route('student.exams') }}" wire:navigate
               class="group flex flex-col items-center justify-center text-center p-6 rounded-2xl bg-[#fdeded] hover:bg-[#fbdcdc] border border-[#ffcdd2] dark:bg-rose-950/40 dark:hover:bg-rose-900/50 dark:border-rose-800/50 transition-all duration-200 shadow-sm hover:shadow-md cursor-pointer">
                <div class="w-12 h-12 rounded-full bg-rose-100 dark:bg-rose-900/60 flex items-center justify-center text-[#b71c1c] dark:text-rose-300 mb-3 group-hover:scale-105 transition-transform">
                    <x-ui.icon name="o-clipboard-document-check" class="w-6 h-6" />
                </div>
                <span class="text-base font-bold text-[#b71c1c] dark:text-rose-200 tracking-tight">
                    {{ __('Ulangan & Ujian') }}
                </span>
                <span class="text-xs font-semibold text-rose-700 dark:text-rose-400 mt-1">
                    @if($activeExamsCount > 0)
                        <span class="inline-flex items-center gap-1.5 font-bold text-rose-600 dark:text-rose-300">
                            <span class="w-2 h-2 rounded-full bg-rose-500 animate-ping"></span>
                            {{ $activeExamsCount }} {{ __('Perlu Dikerjakan') }}
                        </span>
                    @else
                        {{ __('Tidak Ada Ujian Aktif') }}
                    @endif
                </span>
            </a>

            {{-- Ubin 3: Hasil & Rekap Nilai (Solid Royal Blue) --}}
            <a href="#riwayat-nilai"
               class="group flex flex-col items-center justify-center text-center p-6 rounded-2xl bg-[#002fbe] hover:bg-[#0027a3] dark:bg-blue-700 dark:hover:bg-blue-600 text-white transition-all duration-200 shadow-md shadow-blue-600/20 cursor-pointer">
                <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center text-white mb-3 group-hover:scale-105 transition-transform">
                    <x-ui.icon name="o-document-chart-bar" class="w-6 h-6" />
                </div>
                <span class="text-base font-bold text-white tracking-tight">
                    {{ __('Hasil & Rekap Nilai') }}
                </span>
                <span class="text-xs font-medium text-blue-100 mt-1">
                    Rata-rata: <strong class="text-white">{{ $avgScore > 0 ? $avgScore : '-' }}</strong>
                </span>
            </a>

            {{-- Ubin 4: Profil Siswa (Warm Sand) --}}
            <a href="{{ route('profile.edit') }}" wire:navigate
               class="group flex flex-col items-center justify-center text-center p-6 rounded-2xl bg-[#fcf8e3] hover:bg-[#fbf4d0] border border-[#faebcc] dark:bg-amber-950/40 dark:hover:bg-amber-900/50 dark:border-amber-800/50 transition-all duration-200 shadow-sm hover:shadow-md cursor-pointer">
                <div class="w-12 h-12 rounded-full bg-amber-100 dark:bg-amber-900/60 flex items-center justify-center text-[#8a6d3b] dark:text-amber-300 mb-3 group-hover:scale-105 transition-transform">
                    <x-ui.icon name="o-user-circle" class="w-6 h-6" />
                </div>
                <span class="text-base font-bold text-[#68490a] dark:text-amber-200 tracking-tight">
                    {{ __('Profil & Biodata') }}
                </span>
                <span class="text-xs font-semibold text-amber-800 dark:text-amber-400 mt-1">
                    {{ __('Pengaturan Akun') }}
                </span>
            </a>

        </div>
    </div>

    {{-- Main Content Grid: 2 Kolom Fungsional --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        
        {{-- Kolom Kiri (2 Span): Ulangan Aktif & Materi Pembelajaran --}}
        <div class="lg:col-span-2 space-y-6">
            
            {{-- Ulangan yang Perlu Dikerjakan Segera --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-1.5 h-4 bg-rose-600 rounded-full"></div>
                        <h3 class="font-bold text-base text-slate-900 dark:text-white">
                            {{ __('Ulangan yang Perlu Dikerjakan') }}
                        </h3>
                    </div>
                    <a href="{{ route('student.exams') }}" class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline" wire:navigate>
                        {{ __('Lihat Semua →') }}
                    </a>
                </div>

                @if($activeExams->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($activeExams as $exam)
                            <div class="p-4 rounded-xl border border-rose-200 dark:border-rose-900/50 bg-rose-50/30 dark:bg-rose-950/20 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-rose-100 text-rose-700 dark:bg-rose-900/60 dark:text-rose-300">
                                            {{ $exam->exam_type }}
                                        </span>
                                        <h4 class="font-semibold text-sm text-slate-900 dark:text-white">
                                            {{ $exam->title }}
                                        </h4>
                                    </div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ $exam->subject?->name }} • {{ $exam->questions_count }} Butir Soal • Durasi {{ $exam->duration_minutes ?? '∞' }} menit
                                    </p>
                                    @if($exam->end_time)
                                        <p class="text-xs text-amber-600 dark:text-amber-400 font-medium">
                                            Batas: {{ $exam->end_time->format('d M Y, H:i') }}
                                        </p>
                                    @endif
                                </div>
                                <x-ui.button :label="__('Mulai Kerjakan')" :link="route('student.take-exam', $exam->id)" icon="o-play" class="btn-primary btn-sm shrink-0" />
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 px-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-dashed border-slate-200 dark:border-slate-700">
                        <x-ui.icon name="o-check-circle" class="w-10 h-10 text-emerald-500 mx-auto mb-2" />
                        <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300">
                            {{ __('Tidak ada ulangan aktif saat ini') }}
                        </h4>
                        <p class="text-xs text-slate-500 mt-1">
                            {{ __('Semua ulangan telah Anda kerjakan atau belum ada jadwal ujian baru dari pengajar.') }}
                        </p>
                    </div>
                @endif
            </div>

            {{-- Materi Pembelajaran Terbaru --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-1.5 h-4 bg-emerald-600 rounded-full"></div>
                        <h3 class="font-bold text-base text-slate-900 dark:text-white">
                            {{ __('Materi Pembelajaran Terbaru') }}
                        </h3>
                    </div>
                    <a href="{{ route('student.materials') }}" class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:underline" wire:navigate>
                        {{ __('Lihat Semua Materi →') }}
                    </a>
                </div>

                <div class="space-y-3">
                    @forelse($recentMaterials as $material)
                        <a href="{{ route('student.material-detail', $material->id) }}"
                           class="block p-3.5 rounded-xl border border-slate-100 dark:border-slate-700/60 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors"
                           wire:navigate>
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h4 class="font-semibold text-sm text-slate-900 dark:text-white">
                                        {{ $material->title }}
                                    </h4>
                                    <div class="text-xs text-slate-500 mt-1 flex flex-wrap items-center gap-2">
                                        <span>{{ $material->subject?->name }}</span>
                                        <span>•</span>
                                        <span>{{ $material->classroom?->name }}</span>
                                        @if(!empty($material->attachments))
                                            <span>•</span>
                                            <span class="text-emerald-600 dark:text-emerald-400 font-medium">
                                                {{ count($material->attachments) }} lampiran file
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <span class="text-xs text-slate-400 shrink-0">
                                    {{ $material->published_at?->format('d/m/Y') ?? '-' }}
                                </span>
                            </div>
                        </a>
                    @empty
                        <p class="text-center py-6 text-xs text-slate-500">
                            {{ __('Belum ada materi pembelajaran yang dirilis untuk kelas Anda.') }}
                        </p>
                    @endforelse
                </div>
            </div>

        </div>

        {{-- Kolom Kanan (1 Span): Riwayat & Rekap Nilai --}}
        <div id="riwayat-nilai" class="space-y-6">
            
            {{-- Kartu Ringkasan Capaian --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 shadow-sm space-y-4">
                <h3 class="font-bold text-base text-slate-900 dark:text-white">
                    {{ __('Ringkasan Nilai') }}
                </h3>

                <div class="grid grid-cols-2 gap-3 text-center">
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-700/40 border border-slate-100 dark:border-slate-700">
                        <div class="text-xs text-slate-500">{{ __('Ulangan Selesai') }}</div>
                        <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ $totalExamsDone }}</div>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-700/40 border border-slate-100 dark:border-slate-700">
                        <div class="text-xs text-slate-500">{{ __('Rata-rata Nilai') }}</div>
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $avgScore > 0 ? $avgScore : '-' }}</div>
                    </div>
                </div>
            </div>

            {{-- Daftar Riwayat Nilai Terakhir --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 shadow-sm space-y-4">
                <h3 class="font-bold text-base text-slate-900 dark:text-white">
                    {{ __('Riwayat Nilai Ulangan') }}
                </h3>

                <div class="space-y-2.5">
                    @forelse($recentSubmissions as $submission)
                        <div class="p-3 rounded-xl border border-slate-100 dark:border-slate-700 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <h4 class="font-semibold text-xs text-slate-900 dark:text-white truncate">
                                    {{ $submission->exam?->title }}
                                </h4>
                                <p class="text-[11px] text-slate-500">
                                    {{ $submission->exam?->subject?->name }} • {{ $submission->submitted_at?->format('d/m/Y') ?? '-' }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                @if($submission->total_score !== null)
                                    @php
                                        $passing = $submission->exam?->passing_grade ?? 70;
                                        $isPassed = (float) $submission->total_score >= $passing;
                                    @endphp
                                    <div class="text-right">
                                        <div class="text-sm font-bold {{ $isPassed ? 'text-emerald-600' : 'text-rose-600' }}">
                                            {{ number_format($submission->total_score, 1) }}
                                        </div>
                                    </div>
                                @else
                                    <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-amber-100 text-amber-800">
                                        {{ __('Menunggu') }}
                                    </span>
                                @endif
                                <a href="{{ route('student.exam-result', $submission->id) }}"
                                   class="p-1 rounded text-slate-400 hover:text-blue-600 transition-colors"
                                   title="{{ __('Detail Hasil') }}"
                                   wire:navigate>
                                    <x-ui.icon name="o-arrow-right" class="w-4 h-4" />
                                </a>
                            </div>
                        </div>
                    @empty
                        <p class="text-center py-6 text-xs text-slate-400">
                            {{ __('Belum ada riwayat pengerjaan ulangan.') }}
                        </p>
                    @endforelse
                </div>
            </div>

        </div>

    </div>
</div>
