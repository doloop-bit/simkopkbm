<?php

declare(strict_types=1);

use App\Models\AcademicYear;
use App\Models\OnlineExam;
use App\Models\OnlineExamSubmission;
use App\Models\OnlineMaterial;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.student')] class extends Component {
    public bool $guideModal = false;
    public bool $scheduleModal = false;
    public bool $kkmModal = false;
    public bool $helpModal = false;
    public bool $progressModal = false;
    public bool $transcriptModal = false;
    public bool $letterModal = false;

    public function openGuide(): void
    {
        $this->guideModal = true;
    }

    public function openSchedule(): void
    {
        $this->scheduleModal = true;
    }

    public function openKkm(): void
    {
        $this->kkmModal = true;
    }

    public function openHelp(): void
    {
        $this->helpModal = true;
    }

    public function openProgress(): void
    {
        $this->progressModal = true;
    }

    public function openTranscript(): void
    {
        $this->transcriptModal = true;
    }

    public function openLetter(): void
    {
        $this->letterModal = true;
    }

    public function with(): array
    {
        $student = auth()->user();
        $studentId = $student->id;

        $studentProfile = $student->studentProfile ?? $student->latestProfile?->profileable;
        $classroom = $studentProfile?->classroom;
        $classroomId = $classroom?->id;
        $level = $classroom?->level;

        $activeYear = $classroom?->academicYear ?? AcademicYear::where('is_active', true)->first();

        $totalMaterials = OnlineMaterial::query()
            ->where('is_published', true)
            ->when($classroomId, fn ($q) => $q->where('classroom_id', $classroomId))
            ->count();

        $recentMaterials = OnlineMaterial::query()
            ->where('is_published', true)
            ->when($classroomId, fn ($q) => $q->where('classroom_id', $classroomId))
            ->with(['subject', 'classroom'])
            ->latest('published_at')
            ->limit(4)
            ->get();

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
            ->limit(4)
            ->get();

        $submissions = OnlineExamSubmission::query()
            ->where('student_id', $studentId)
            ->with(['exam.subject'])
            ->latest('submitted_at')
            ->get();

        $recentSubmissions = $submissions->take(5);
        $totalExamsDone = $submissions->count();
        $gradedSubmissions = $submissions->where('status', 'graded');
        $avgScore = $gradedSubmissions->avg('total_score');
        $passedCount = $gradedSubmissions->filter(fn ($s) => (float) $s->total_score >= ($s->exam?->passing_grade ?? 70))->count();
        $passRate = $gradedSubmissions->count() > 0 ? round(($passedCount / $gradedSubmissions->count()) * 100) : 0;

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
            'recentSubmissions' => $recentSubmissions,
            'allSubmissions' => $submissions,
            'totalExamsDone' => $totalExamsDone,
            'gradedCount' => $gradedSubmissions->count(),
            'avgScore' => round($avgScore ?? 0, 1),
            'passedCount' => $passedCount,
            'passRate' => $passRate,
        ];
    }
}; ?>

<div class="p-4 sm:p-6 lg:p-8 space-y-8 max-w-7xl mx-auto">
    {{-- Top University Student ID Banner --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 text-white p-6 sm:p-8 shadow-xl border border-blue-800/40">
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute right-12 top-6 opacity-10 hidden md:block">
            <x-ui.icon name="o-academic-cap" class="w-40 h-40 text-white" />
        </div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div class="flex items-start sm:items-center gap-4 sm:gap-6">
                <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center text-white font-bold text-2xl sm:text-3xl shrink-0 shadow-inner">
                    {{ strtoupper(substr($student->name, 0, 1)) }}
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2 mb-1.5">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                            {{ __('Siswa Daring Aktif') }}
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-500/20 text-blue-200 border border-blue-400/30">
                            {{ $academicYear?->name ?? 'Tahun Ajaran Aktif' }}
                        </span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white mb-2">
                        {{ $student->name }}
                    </h1>
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs sm:text-sm text-slate-300">
                        <span class="flex items-center gap-1.5">
                            <x-ui.icon name="o-identification" class="w-4 h-4 text-blue-400" />
                            NIS: <strong class="text-white">{{ $studentProfile?->nis ?? '-' }}</strong>
                        </span>
                        <span>•</span>
                        <span class="flex items-center gap-1.5">
                            <x-ui.icon name="o-finger-print" class="w-4 h-4 text-indigo-400" />
                            NISN: <strong class="text-white">{{ $studentProfile?->nisn ?? '-' }}</strong>
                        </span>
                        <span>•</span>
                        <span class="flex items-center gap-1.5">
                            <x-ui.icon name="o-building-library" class="w-4 h-4 text-emerald-400" />
                            Kelas: <strong class="text-white">{{ $classroom?->name ?? ($level?->name ?? 'Daring') }}</strong>
                        </span>
                    </div>
                </div>
            </div>

            {{-- Quick Summary Badges --}}
            <div class="flex sm:flex-row md:flex-col justify-between sm:justify-start gap-3 bg-white/5 backdrop-blur-sm p-3.5 rounded-xl border border-white/10 shrink-0">
                <div class="text-left sm:text-right">
                    <div class="text-xs text-slate-300 font-medium">{{ __('Rata-rata Nilai') }}</div>
                    <div class="text-2xl font-black text-amber-300">{{ $avgScore > 0 ? $avgScore : 'Belum Ada' }}</div>
                </div>
                <div class="text-left sm:text-right border-l sm:border-l-0 md:border-t border-white/10 pl-3 sm:pl-0 md:pt-2">
                    <div class="text-xs text-slate-300 font-medium">{{ __('Ulangan Selesai') }}</div>
                    <div class="text-base font-bold text-emerald-300">{{ $totalExamsDone }} <span class="text-xs font-normal text-slate-400">paket</span></div>
                </div>
            </div>
        </div>
    </div>

    {{-- SECTION: BLOK AKADEMIK (University Portal Style Tiles Grid) --}}
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-1.5 h-6 bg-blue-700 rounded-full"></div>
                <h2 class="text-lg font-extrabold tracking-wider text-slate-900 dark:text-white uppercase">
                    {{ __('BLOK AKADEMIK') }}
                </h2>
            </div>
            <span class="text-xs text-slate-500 font-medium hidden sm:inline">
                {{ __('Pusat Layanan & Pembelajaran Mandiri Siswa Daring') }}
            </span>
        </div>

        {{-- 4-Column University Tiles Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            
            {{-- Tile 1: Materi Pelajaran (Soft Green) --}}
            <a href="{{ route('student.materials') }}" wire:navigate
               class="group relative flex flex-col items-center justify-center text-center p-6 min-h-[145px] rounded-xl bg-[#ecf7ed] hover:bg-[#e2f3e3] border border-[#c8e6c9] dark:bg-emerald-950/40 dark:hover:bg-emerald-900/50 dark:border-emerald-800/50 transition-all duration-200 shadow-sm hover:shadow-md hover:-translate-y-0.5 cursor-pointer">
                <div class="w-11 h-11 rounded-full bg-emerald-100 dark:bg-emerald-900/60 flex items-center justify-center text-[#1b5e20] dark:text-emerald-300 mb-2.5 group-hover:scale-110 transition-transform">
                    <x-ui.icon name="o-book-open" class="w-6 h-6" />
                </div>
                <span class="text-base font-bold text-[#1b5e20] dark:text-emerald-200 tracking-tight leading-snug">
                    {{ __('Materi Pelajaran') }}
                </span>
                <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-400 mt-1">
                    {{ $totalMaterials }} {{ __('Modul Tersedia') }}
                </span>
            </a>

            {{-- Tile 2: Ulangan & Ujian (Soft Rose) --}}
            <a href="{{ route('student.exams') }}" wire:navigate
               class="group relative flex flex-col items-center justify-center text-center p-6 min-h-[145px] rounded-xl bg-[#fdeded] hover:bg-[#fbdcdc] border border-[#ffcdd2] dark:bg-rose-950/40 dark:hover:bg-rose-900/50 dark:border-rose-800/50 transition-all duration-200 shadow-sm hover:shadow-md hover:-translate-y-0.5 cursor-pointer">
                <div class="w-11 h-11 rounded-full bg-rose-100 dark:bg-rose-900/60 flex items-center justify-center text-[#b71c1c] dark:text-rose-300 mb-2.5 group-hover:scale-110 transition-transform">
                    <x-ui.icon name="o-clipboard-document-check" class="w-6 h-6" />
                </div>
                <span class="text-base font-bold text-[#b71c1c] dark:text-rose-200 tracking-tight leading-snug">
                    {{ __('Ulangan & Ujian') }}
                </span>
                <span class="text-xs font-semibold text-rose-700 dark:text-rose-400 mt-1">
                    @if($activeExamsCount > 0)
                        <span class="inline-flex items-center gap-1 font-bold text-rose-600">
                            <span class="w-2 h-2 rounded-full bg-rose-500 animate-ping"></span>
                            {{ $activeExamsCount }} {{ __('Sedang Aktif') }}
                        </span>
                    @else
                        {{ __('Lihat Daftar Ujian') }}
                    @endif
                </span>
            </a>

            {{-- Tile 3: Panduan Perkuliahan / Belajar (Solid Royal Blue) --}}
            <button type="button" wire:click="openGuide"
               class="group relative flex flex-col items-center justify-center text-center p-6 min-h-[145px] rounded-xl bg-[#002fbe] hover:bg-[#0027a3] dark:bg-blue-700 dark:hover:bg-blue-600 text-white transition-all duration-200 shadow-md shadow-blue-600/20 hover:-translate-y-0.5 cursor-pointer">
                <div class="w-11 h-11 rounded-full bg-white/20 flex items-center justify-center text-white mb-2.5 group-hover:scale-110 transition-transform">
                    <x-ui.icon name="o-academic-cap" class="w-6 h-6" />
                </div>
                <span class="text-base font-bold text-white tracking-tight leading-snug">
                    {{ __('Panduan Siswa Daring') }}
                </span>
                <span class="text-xs font-medium text-blue-100 mt-1">
                    {{ __('Petunjuk & Tata Tertib') }}
                </span>
            </button>

            {{-- Tile 4: Perpustakaan / Sumber Belajar (Warm Cream) --}}
            <a href="{{ route('student.materials') }}" wire:navigate
               class="group relative flex flex-col items-center justify-center text-center p-6 min-h-[145px] rounded-xl bg-[#fcf8e3] hover:bg-[#fbf4d0] border border-[#faebcc] dark:bg-amber-950/40 dark:hover:bg-amber-900/50 dark:border-amber-800/50 transition-all duration-200 shadow-sm hover:shadow-md hover:-translate-y-0.5 cursor-pointer">
                <div class="w-11 h-11 rounded-full bg-amber-100 dark:bg-amber-900/60 flex items-center justify-center text-[#8a6d3b] dark:text-amber-300 mb-2.5 group-hover:scale-110 transition-transform">
                    <x-ui.icon name="o-folder-arrow-down" class="w-6 h-6" />
                </div>
                <span class="text-base font-bold text-[#68490a] dark:text-amber-200 tracking-tight leading-snug">
                    {{ __('Perpustakaan Digital') }}
                </span>
                <span class="text-xs font-semibold text-amber-800 dark:text-amber-400 mt-1">
                    {{ __('Unduh Modul & PDF') }}
                </span>
            </a>

            {{-- Tile 5: Jadwal Kegiatan (Soft Rose) --}}
            <button type="button" wire:click="openSchedule"
               class="group relative flex flex-col items-center justify-center text-center p-6 min-h-[145px] rounded-xl bg-[#fdeded] hover:bg-[#fbdcdc] border border-[#ffcdd2] dark:bg-rose-950/40 dark:hover:bg-rose-900/50 dark:border-rose-800/50 transition-all duration-200 shadow-sm hover:shadow-md hover:-translate-y-0.5 cursor-pointer">
                <div class="w-11 h-11 rounded-full bg-rose-100 dark:bg-rose-900/60 flex items-center justify-center text-[#b71c1c] dark:text-rose-300 mb-2.5 group-hover:scale-110 transition-transform">
                    <x-ui.icon name="o-calendar-days" class="w-6 h-6" />
                </div>
                <span class="text-base font-bold text-[#b71c1c] dark:text-rose-200 tracking-tight leading-snug">
                    {{ __('Jadwal Kegiatan') }}
                </span>
                <span class="text-xs font-semibold text-rose-700 dark:text-rose-400 mt-1">
                    {{ __('Agenda Pembelajaran') }}
                </span>
            </button>

            {{-- Tile 6: Registrasi & Pendaftaran Ujian (Solid Royal Blue) --}}
            <a href="{{ route('student.exams') }}" wire:navigate
               class="group relative flex flex-col items-center justify-center text-center p-6 min-h-[145px] rounded-xl bg-[#002fbe] hover:bg-[#0027a3] dark:bg-blue-700 dark:hover:bg-blue-600 text-white transition-all duration-200 shadow-md shadow-blue-600/20 hover:-translate-y-0.5 cursor-pointer">
                <div class="w-11 h-11 rounded-full bg-white/20 flex items-center justify-center text-white mb-2.5 group-hover:scale-110 transition-transform">
                    <x-ui.icon name="o-queue-list" class="w-6 h-6" />
                </div>
                <span class="text-base font-bold text-white tracking-tight leading-snug">
                    {{ __('Registrasi Ujian Daring') }}
                </span>
                <span class="text-xs font-medium text-blue-100 mt-1">
                    {{ __('UTS, UAS & Ujian Harian') }}
                </span>
            </a>

            {{-- Tile 7: Daftar Nilai & Transkrip (Warm Cream) --}}
            <button type="button" wire:click="openTranscript"
               class="group relative flex flex-col items-center justify-center text-center p-6 min-h-[145px] rounded-xl bg-[#fcf8e3] hover:bg-[#fbf4d0] border border-[#faebcc] dark:bg-amber-950/40 dark:hover:bg-amber-900/50 dark:border-amber-800/50 transition-all duration-200 shadow-sm hover:shadow-md hover:-translate-y-0.5 cursor-pointer">
                <div class="w-11 h-11 rounded-full bg-amber-100 dark:bg-amber-900/60 flex items-center justify-center text-[#8a6d3b] dark:text-amber-300 mb-2.5 group-hover:scale-110 transition-transform">
                    <x-ui.icon name="o-document-chart-bar" class="w-6 h-6" />
                </div>
                <span class="text-base font-bold text-[#68490a] dark:text-amber-200 tracking-tight leading-snug">
                    {{ __('Daftar Nilai') }}
                </span>
                <span class="text-xs font-semibold text-amber-800 dark:text-amber-400 mt-1">
                    {{ __('Rekap & Transkrip Skor') }}
                </span>
            </button>

            {{-- Tile 8: Status Kelulusan & KKM (Soft Green) --}}
            <button type="button" wire:click="openKkm"
               class="group relative flex flex-col items-center justify-center text-center p-6 min-h-[145px] rounded-xl bg-[#ecf7ed] hover:bg-[#e2f3e3] border border-[#c8e6c9] dark:bg-emerald-950/40 dark:hover:bg-emerald-900/50 dark:border-emerald-800/50 transition-all duration-200 shadow-sm hover:shadow-md hover:-translate-y-0.5 cursor-pointer">
                <div class="w-11 h-11 rounded-full bg-emerald-100 dark:bg-emerald-900/60 flex items-center justify-center text-[#1b5e20] dark:text-emerald-300 mb-2.5 group-hover:scale-110 transition-transform">
                    <x-ui.icon name="o-check-badge" class="w-6 h-6" />
                </div>
                <span class="text-base font-bold text-[#1b5e20] dark:text-emerald-200 tracking-tight leading-snug">
                    {{ __('Status Kelulusan & KKM') }}
                </span>
                <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-400 mt-1">
                    {{ __('Standar Kelulusan Ujian') }}
                </span>
            </button>

            {{-- Tile 9: Surat Keterangan Siswa (Soft Rose) --}}
            <button type="button" wire:click="openLetter"
               class="group relative flex flex-col items-center justify-center text-center p-6 min-h-[145px] rounded-xl bg-[#fdeded] hover:bg-[#fbdcdc] border border-[#ffcdd2] dark:bg-rose-950/40 dark:hover:bg-rose-900/50 dark:border-rose-800/50 transition-all duration-200 shadow-sm hover:shadow-md hover:-translate-y-0.5 cursor-pointer">
                <div class="w-11 h-11 rounded-full bg-rose-100 dark:bg-rose-900/60 flex items-center justify-center text-[#b71c1c] dark:text-rose-300 mb-2.5 group-hover:scale-110 transition-transform">
                    <x-ui.icon name="o-envelope" class="w-6 h-6" />
                </div>
                <span class="text-base font-bold text-[#b71c1c] dark:text-rose-200 tracking-tight leading-snug">
                    {{ __('Surat Keterangan') }}
                </span>
                <span class="text-xs font-semibold text-rose-700 dark:text-rose-400 mt-1">
                    {{ __('Surat Siswa Aktif Daring') }}
                </span>
            </button>

            {{-- Tile 10: Target & Progres Belajar (Solid Royal Blue) --}}
            <button type="button" wire:click="openProgress"
               class="group relative flex flex-col items-center justify-center text-center p-6 min-h-[145px] rounded-xl bg-[#002fbe] hover:bg-[#0027a3] dark:bg-blue-700 dark:hover:bg-blue-600 text-white transition-all duration-200 shadow-md shadow-blue-600/20 hover:-translate-y-0.5 cursor-pointer">
                <div class="w-11 h-11 rounded-full bg-white/20 flex items-center justify-center text-white mb-2.5 group-hover:scale-110 transition-transform">
                    <x-ui.icon name="o-chart-bar" class="w-6 h-6" />
                </div>
                <span class="text-base font-bold text-white tracking-tight leading-snug">
                    {{ __('Target & Progres Belajar') }}
                </span>
                <span class="text-xs font-medium text-blue-100 mt-1">
                    {{ $totalExamsDone }} {{ __('Tugas Diselesaikan') }}
                </span>
            </button>

            {{-- Tile 11: Layanan & Konsultasi (Warm Cream) --}}
            <button type="button" wire:click="openHelp"
               class="group relative flex flex-col items-center justify-center text-center p-6 min-h-[145px] rounded-xl bg-[#fcf8e3] hover:bg-[#fbf4d0] border border-[#faebcc] dark:bg-amber-950/40 dark:hover:bg-amber-900/50 dark:border-amber-800/50 transition-all duration-200 shadow-sm hover:shadow-md hover:-translate-y-0.5 cursor-pointer">
                <div class="w-11 h-11 rounded-full bg-amber-100 dark:bg-amber-900/60 flex items-center justify-center text-[#8a6d3b] dark:text-amber-300 mb-2.5 group-hover:scale-110 transition-transform">
                    <x-ui.icon name="o-chat-bubble-left-right" class="w-6 h-6" />
                </div>
                <span class="text-base font-bold text-[#68490a] dark:text-amber-200 tracking-tight leading-snug">
                    {{ __('Layanan & Konsultasi') }}
                </span>
                <span class="text-xs font-semibold text-amber-800 dark:text-amber-400 mt-1">
                    {{ __('Bantuan Teknis & Guru') }}
                </span>
            </button>

            {{-- Tile 12: Profil Siswa (Soft Green) --}}
            <a href="{{ route('profile.edit') }}" wire:navigate
               class="group relative flex flex-col items-center justify-center text-center p-6 min-h-[145px] rounded-xl bg-[#ecf7ed] hover:bg-[#e2f3e3] border border-[#c8e6c9] dark:bg-emerald-950/40 dark:hover:bg-emerald-900/50 dark:border-emerald-800/50 transition-all duration-200 shadow-sm hover:shadow-md hover:-translate-y-0.5 cursor-pointer">
                <div class="w-11 h-11 rounded-full bg-emerald-100 dark:bg-emerald-900/60 flex items-center justify-center text-[#1b5e20] dark:text-emerald-300 mb-2.5 group-hover:scale-110 transition-transform">
                    <x-ui.icon name="o-user-circle" class="w-6 h-6" />
                </div>
                <span class="text-base font-bold text-[#1b5e20] dark:text-emerald-200 tracking-tight leading-snug">
                    {{ __('Profil & Biodata') }}
                </span>
                <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-400 mt-1">
                    {{ __('Data Diri & Akun') }}
                </span>
            </a>

        </div>
    </div>

    {{-- Urgent Active Exams Section --}}
    @if($activeExams->isNotEmpty())
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-1.5 h-5 bg-rose-600 rounded-full"></div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                        {{ __('Ulangan Online yang Perlu Dikerjakan Segera') }}
                    </h3>
                </div>
                <a href="{{ route('student.exams') }}" class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline" wire:navigate>
                    {{ __('Lihat Semua Ulangan →') }}
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($activeExams as $exam)
                    <div class="p-5 rounded-2xl bg-white dark:bg-slate-800 border-2 border-rose-200 dark:border-rose-900/50 shadow-sm flex flex-col justify-between gap-4">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">
                                    {{ strtoupper($exam->exam_type) }}
                                </span>
                                @if($exam->duration_minutes)
                                    <span class="text-xs font-medium text-slate-500 flex items-center gap-1">
                                        <x-ui.icon name="o-clock" class="w-4 h-4 text-slate-400" />
                                        {{ $exam->duration_minutes }} menit
                                    </span>
                                @endif
                            </div>
                            <h4 class="font-bold text-base text-slate-900 dark:text-white line-clamp-1">
                                {{ $exam->title }}
                            </h4>
                            <p class="text-xs text-slate-500 mt-1">
                                {{ $exam->subject?->name }} • {{ $exam->classroom?->name }} • {{ $exam->questions_count }} Butir Soal
                            </p>
                            @if($exam->end_time)
                                <div class="text-xs font-medium text-amber-600 dark:text-amber-400 mt-2 flex items-center gap-1">
                                    <x-ui.icon name="o-exclamation-circle" class="w-4 h-4" />
                                    Batas Akhir: {{ $exam->end_time->format('d M Y, H:i') }}
                                </div>
                            @endif
                        </div>
                        <div class="pt-3 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
                            <span class="text-xs text-slate-500">KKM: <strong>{{ $exam->passing_grade ?? 75 }}</strong></span>
                            <x-ui.button :label="__('Mulai Kerjakan')" :link="route('student.take-exam', $exam->id)" icon="o-play" class="btn-primary btn-sm" />
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Bottom 2-Column: Recent Materials & Recent Submissions --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        {{-- Recent Materials --}}
        <x-ui.card shadow :title="__('Materi Pembelajaran Terkini')">
            <div class="space-y-3">
                @forelse($recentMaterials as $material)
                    <a href="{{ route('student.material-detail', $material->id) }}"
                       class="group block p-3.5 rounded-xl border border-slate-100 dark:border-slate-700/60 hover:border-emerald-300 dark:hover:border-emerald-600/50 hover:bg-emerald-50/30 dark:hover:bg-emerald-950/20 transition-all"
                       wire:navigate>
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h4 class="font-semibold text-sm text-slate-900 dark:text-white group-hover:text-emerald-700 dark:group-hover:text-emerald-400 transition-colors">
                                    {{ $material->title }}
                                </h4>
                                <div class="text-xs text-slate-500 mt-1 flex flex-wrap items-center gap-2">
                                    <span>{{ $material->subject?->name }}</span>
                                    <span>•</span>
                                    <span>{{ $material->classroom?->name }}</span>
                                    @if(!empty($material->attachments))
                                        <span>•</span>
                                        <span class="text-emerald-600 dark:text-emerald-400 font-medium">
                                            {{ count($material->attachments) }} file lampiran
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <x-ui.icon name="o-arrow-right" class="w-4 h-4 text-slate-400 group-hover:text-emerald-600 group-hover:translate-x-1 transition-all shrink-0 mt-1" />
                        </div>
                    </a>
                @empty
                    <div class="text-center py-8">
                        <x-ui.icon name="o-book-open" class="w-10 h-10 text-slate-300 mx-auto mb-2" />
                        <p class="text-sm text-slate-500">{{ __('Belum ada materi pembelajaran yang dirilis.') }}</p>
                    </div>
                @endforelse
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-700">
                <a href="{{ route('student.materials') }}" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline flex items-center gap-1" wire:navigate>
                    {{ __('Buka Semua Materi Pelajaran') }}
                    <x-ui.icon name="o-arrow-right" class="w-3.5 h-3.5" />
                </a>
            </div>
        </x-ui.card>

        {{-- Recent Submissions / Exam History --}}
        <x-ui.card shadow :title="__('Riwayat Pengerjaan & Nilai Terakhir')">
            @if($recentSubmissions->isNotEmpty())
                <div class="space-y-3">
                    @foreach($recentSubmissions as $submission)
                        <div class="p-3.5 rounded-xl border border-slate-100 dark:border-slate-700/60 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <h4 class="font-semibold text-sm text-slate-900 dark:text-white truncate">
                                    {{ $submission->exam?->title }}
                                </h4>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    {{ $submission->exam?->subject?->name }} • {{ $submission->submitted_at?->format('d M Y, H:i') ?? '-' }}
                                </div>
                            </div>
                            <div class="flex items-center gap-3 shrink-0">
                                @if($submission->total_score !== null)
                                    @php
                                        $passing = $submission->exam?->passing_grade ?? 70;
                                        $isPassed = (float) $submission->total_score >= $passing;
                                    @endphp
                                    <div class="text-right">
                                        <div class="text-base font-bold {{ $isPassed ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                            {{ number_format($submission->total_score, 1) }}
                                        </div>
                                        <span class="text-[10px] font-bold px-1.5 py-0.2 rounded {{ $isPassed ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                            {{ $isPassed ? 'LULUS' : 'REMEDIAL' }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">
                                        {{ __('Menunggu Nilai') }}
                                    </span>
                                @endif
                                <a href="{{ route('student.exam-result', $submission->id) }}"
                                   class="p-1.5 text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                                   title="{{ __('Lihat Detail') }}"
                                   wire:navigate>
                                    <x-ui.icon name="o-eye" class="w-4 h-4" />
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-700 flex justify-between items-center">
                    <span class="text-xs text-slate-500">Rata-rata: <strong class="text-slate-900 dark:text-white">{{ $avgScore }}</strong></span>
                    <button type="button" wire:click="openTranscript" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline">
                        {{ __('Lihat Transkrip Lengkap →') }}
                    </button>
                </div>
            @else
                <div class="text-center py-8">
                    <x-ui.icon name="o-document-chart-bar" class="w-10 h-10 text-slate-300 mx-auto mb-2" />
                    <p class="text-sm text-slate-500">{{ __('Belum ada riwayat ulangan yang dikerjakan.') }}</p>
                </div>
            @endif
        </x-ui.card>

    </div>

    {{-- MODALS FOR INTERACTIVE TILES --}}

    {{-- Modal 1: Panduan Belajar & Ujian Daring --}}
    <x-ui.modal wire:model="guideModal" :title="__('Panduan Pembelajaran & Ujian Siswa Daring')" maxWidth="max-w-2xl">
        <div class="space-y-4 text-slate-700 dark:text-slate-300 text-sm">
            <div class="p-4 rounded-xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-900/50">
                <h4 class="font-bold text-blue-900 dark:text-blue-200 text-base mb-1">
                    {{ __('Tata Tertib & Ketentuan Pembelajaran Daring (E-Learning)') }}
                </h4>
                <p class="text-xs text-blue-800 dark:text-blue-300">
                    {{ __('Harap perhatikan panduan berikut agar proses belajar dan penilaian berjalan lancar.') }}
                </p>
            </div>

            <div class="space-y-3">
                <div class="flex items-start gap-3">
                    <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 font-bold flex items-center justify-center shrink-0 text-xs">1</span>
                    <div>
                        <strong class="text-slate-900 dark:text-white">{{ __('Membaca Materi Pelajaran:') }}</strong>
                        <p class="text-xs text-slate-500 mt-0.5">Siswa wajib membaca modul dan mengunduh berkas lampiran (PDF/gambar) sebelum mengerjakan ulangan terkait.</p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 font-bold flex items-center justify-center shrink-0 text-xs">2</span>
                    <div>
                        <strong class="text-slate-900 dark:text-white">{{ __('Pengerjaan Ulangan & Timer:') }}</strong>
                        <p class="text-xs text-slate-500 mt-0.5">Setiap ulangan memiliki durasi waktu. Waktu pengerjaan akan terus berjalan begitu Anda menekan tombol "Mulai Kerjakan". Pastikan koneksi internet stabil.</p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 font-bold flex items-center justify-center shrink-0 text-xs">3</span>
                    <div>
                        <strong class="text-slate-900 dark:text-white">{{ __('Penyimpanan Otomatis Jawaban:') }}</strong>
                        <p class="text-xs text-slate-500 mt-0.5">Setiap pilihan ganda yang dipilih atau essay yang diketik disimpan secara otomatis oleh sistem ke server.</p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 font-bold flex items-center justify-center shrink-0 text-xs">4</span>
                    <div>
                        <strong class="text-slate-900 dark:text-white">{{ __('Pengumuman Nilai:') }}</strong>
                        <p class="text-xs text-slate-500 mt-0.5">Nilai pilihan ganda dinilai otomatis seketika setelah submit. Soal essay akan dikoreksi manual oleh guru pengampu.</p>
                    </div>
                </div>
            </div>
        </div>
        <x-slot:actions>
            <x-ui.button :label="__('Tutup Panduan')" wire:click="$set('guideModal', false)" class="btn-primary" />
        </x-slot:actions>
    </x-ui.modal>

    {{-- Modal 2: Jadwal Kegiatan & Kalender Akademik --}}
    <x-ui.modal wire:model="scheduleModal" :title="__('Jadwal Kegiatan & Kalender Akademik')" maxWidth="max-w-xl">
        <div class="space-y-4 text-sm">
            <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                <div class="font-bold text-slate-900 dark:text-white mb-1">
                    {{ $academicYear?->name ?? 'Tahun Ajaran Aktif' }}
                </div>
                <div class="text-xs text-slate-500">
                    Jadwal rilis materi dan agenda ujian daring semester berjalan.
                </div>
            </div>

            <div class="space-y-2">
                <div class="p-3 rounded-lg border border-slate-100 dark:border-slate-800 flex justify-between items-center">
                    <div>
                        <div class="font-semibold text-slate-900 dark:text-white">Rilis Modul Materi Mingguan</div>
                        <div class="text-xs text-slate-500">Setiap hari Senin & Rabu</div>
                    </div>
                    <span class="px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-700">Aktif</span>
                </div>
                <div class="p-3 rounded-lg border border-slate-100 dark:border-slate-800 flex justify-between items-center">
                    <div>
                        <div class="font-semibold text-slate-900 dark:text-white">Ulangan Harian & Kuis Bab</div>
                        <div class="text-xs text-slate-500">Sesuai penugasan guru pengampu</div>
                    </div>
                    <span class="px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-700">Reguler</span>
                </div>
                <div class="p-3 rounded-lg border border-slate-100 dark:border-slate-800 flex justify-between items-center">
                    <div>
                        <div class="font-semibold text-slate-900 dark:text-white">Ujian Tengah Semester (UTS)</div>
                        <div class="text-xs text-slate-500">Pekan ke-8 Semester</div>
                    </div>
                    <span class="px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800">Terjadwal</span>
                </div>
                <div class="p-3 rounded-lg border border-slate-100 dark:border-slate-800 flex justify-between items-center">
                    <div>
                        <div class="font-semibold text-slate-900 dark:text-white">Ujian Akhir Semester (UAS)</div>
                        <div class="text-xs text-slate-500">Pekan ke-16 Semester</div>
                    </div>
                    <span class="px-2 py-0.5 rounded text-xs font-semibold bg-rose-100 text-rose-700">Terjadwal</span>
                </div>
            </div>
        </div>
        <x-slot:actions>
            <x-ui.button :label="__('Tutup')" wire:click="$set('scheduleModal', false)" ghost />
        </x-slot:actions>
    </x-ui.modal>

    {{-- Modal 3: Status Kelulusan & KKM --}}
    <x-ui.modal wire:model="kkmModal" :title="__('Standar Kelulusan & Kriteria Ketuntasan (KKM)')" maxWidth="max-w-xl">
        <div class="space-y-4 text-sm">
            <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800">
                <div class="text-2xl font-black text-emerald-700 dark:text-emerald-300 mb-1">
                    KKM Standar: 70 - 75
                </div>
                <p class="text-xs text-emerald-800 dark:text-emerald-400">
                    Nilai minimum yang harus dicapai siswa pada setiap ulangan online untuk dinyatakan Tuntas / Lulus.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-700 text-center">
                    <div class="text-xs text-slate-500 mb-1">Ulangan Lulus</div>
                    <div class="text-2xl font-bold text-emerald-600">{{ $passedCount }}</div>
                </div>
                <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-700 text-center">
                    <div class="text-xs text-slate-500 mb-1">Tingkat Ketuntasan</div>
                    <div class="text-2xl font-bold text-blue-600">{{ $passRate }}%</div>
                </div>
            </div>

            <div class="text-xs text-slate-500">
                Catatan: Jika nilai ulangan belum mencapai batas KKM, Anda dapat menghubungi guru mata pelajaran untuk mendapatkan materi remedial atau pembukaan jadwal ulangan susulan.
            </div>
        </div>
        <x-slot:actions>
            <x-ui.button :label="__('Tutup')" wire:click="$set('kkmModal', false)" class="btn-primary" />
        </x-slot:actions>
    </x-ui.modal>

    {{-- Modal 4: Transkrip Nilai Lengkap --}}
    <x-ui.modal wire:model="transcriptModal" :title="__('Transkrip & Rekap Nilai Siswa')" maxWidth="max-w-2xl">
        <div class="space-y-4">
            <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800">
                <div>
                    <div class="font-bold text-slate-900 dark:text-white">{{ $student->name }}</div>
                    <div class="text-xs text-slate-500">Kelas: {{ $classroom?->name ?? '-' }} • NIS: {{ $studentProfile?->nis ?? '-' }}</div>
                </div>
                <div class="text-right">
                    <div class="text-xs text-slate-500">Rata-rata Keseluruhan</div>
                    <div class="text-xl font-bold text-blue-600">{{ $avgScore }}</div>
                </div>
            </div>

            <div class="max-h-72 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($allSubmissions as $sub)
                    <div class="py-2.5 flex items-center justify-between text-xs sm:text-sm">
                        <div>
                            <div class="font-semibold text-slate-900 dark:text-white">{{ $sub->exam?->title }}</div>
                            <div class="text-xs text-slate-500">{{ $sub->exam?->subject?->name }} • {{ $sub->submitted_at?->format('d/m/Y') }}</div>
                        </div>
                        <div class="text-right">
                            <span class="font-bold text-base {{ (float)$sub->total_score >= 70 ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $sub->total_score !== null ? number_format($sub->total_score, 1) : '-' }}
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-center py-6 text-xs text-slate-400">Belum ada catatan ulangan.</p>
                @endforelse
            </div>
        </div>
        <x-slot:actions>
            <x-ui.button :label="__('Tutup')" wire:click="$set('transcriptModal', false)" ghost />
        </x-slot:actions>
    </x-ui.modal>

    {{-- Modal 5: Bantuan & Layanan Siswa --}}
    <x-ui.modal wire:model="helpModal" :title="__('Layanan Bantuan & Konsultasi Siswa Daring')" maxWidth="max-w-md">
        <div class="space-y-4 text-sm">
            <div class="text-slate-600 dark:text-slate-400 text-xs">
                Mengalami kendala saat membuka materi atau mengerjakan ulangan? Silakan hubungi layanan bantuan sekolah berikut:
            </div>

            <div class="space-y-2.5">
                <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-700 flex items-center gap-3">
                    <x-ui.icon name="o-chat-bubble-left-ellipsis" class="w-6 h-6 text-emerald-600 shrink-0" />
                    <div>
                        <div class="font-semibold text-slate-900 dark:text-white">Bantuan Teknis E-Learning</div>
                        <div class="text-xs text-slate-500">WhatsApp Admin PKBM</div>
                    </div>
                </div>
                <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-700 flex items-center gap-3">
                    <x-ui.icon name="o-academic-cap" class="w-6 h-6 text-blue-600 shrink-0" />
                    <div>
                        <div class="font-semibold text-slate-900 dark:text-white">Wali Kelas & Guru Pembimbing</div>
                        <div class="text-xs text-slate-500">Konsultasi Tugas & Pembelajaran</div>
                    </div>
                </div>
            </div>
        </div>
        <x-slot:actions>
            <x-ui.button :label="__('Tutup')" wire:click="$set('helpModal', false)" class="btn-primary" />
        </x-slot:actions>
    </x-ui.modal>

    {{-- Modal 6: Surat Keterangan Siswa --}}
    <x-ui.modal wire:model="letterModal" :title="__('Surat Keterangan Siswa Aktif')" maxWidth="max-w-md">
        <div class="space-y-3 text-sm">
            <p class="text-xs text-slate-500">
                Surat Keterangan Siswa Aktif diterbitkan secara resmi oleh bagian Tata Usaha PKBM untuk keperluan administrasi atau beasiswa.
            </p>
            <div class="p-3.5 bg-slate-50 dark:bg-slate-800 rounded-xl space-y-1 text-xs">
                <div>Nama: <strong>{{ $student->name }}</strong></div>
                <div>NIS / NISN: <strong>{{ $studentProfile?->nis ?? '-' }} / {{ $studentProfile?->nisn ?? '-' }}</strong></div>
                <div>Status: <span class="text-emerald-600 font-bold">Terdaftar Aktif</span></div>
            </div>
            <p class="text-xs text-slate-500">
                Silakan hubungi Tata Usaha jika Anda memerlukan surat fisik bertanda tangan kepala PKBM dan stempel basah.
            </p>
        </div>
        <x-slot:actions>
            <x-ui.button :label="__('Tutup')" wire:click="$set('letterModal', false)" class="btn-primary" />
        </x-slot:actions>
    </x-ui.modal>

    {{-- Modal 7: Target & Progres Belajar --}}
    <x-ui.modal wire:model="progressModal" :title="__('Target & Progres Pembelajaran Mandiri')" maxWidth="max-w-md">
        <div class="space-y-4 text-sm">
            <div class="space-y-2">
                <div class="flex justify-between text-xs">
                    <span class="font-medium text-slate-700 dark:text-slate-300">Tingkat Ketuntasan Ujian</span>
                    <span class="font-bold text-blue-600">{{ $passRate }}%</span>
                </div>
                <div class="w-full h-3 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                    <div class="h-full bg-blue-600 rounded-full transition-all duration-500" style="width: {{ min(100, max(5, $passRate)) }}%"></div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 text-center">
                <div class="p-3 bg-slate-50 dark:bg-slate-800 rounded-xl">
                    <div class="text-xs text-slate-500">Materi Terbit</div>
                    <div class="text-xl font-bold text-slate-900 dark:text-white">{{ $totalMaterials }}</div>
                </div>
                <div class="p-3 bg-slate-50 dark:bg-slate-800 rounded-xl">
                    <div class="text-xs text-slate-500">Ulangan Selesai</div>
                    <div class="text-xl font-bold text-emerald-600">{{ $totalExamsDone }}</div>
                </div>
            </div>
        </div>
        <x-slot:actions>
            <x-ui.button :label="__('Tutup')" wire:click="$set('progressModal', false)" class="btn-primary" />
        </x-slot:actions>
    </x-ui.modal>
</div>
