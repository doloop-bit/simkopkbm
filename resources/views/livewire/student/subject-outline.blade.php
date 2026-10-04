<?php

declare(strict_types=1);

use App\Models\Subject;
use App\Services\ElearningCourseOutlineService;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.student')] class extends Component {
    public int $subjectId;

    public function mount(int $subjectId): void
    {
        $this->subjectId = $subjectId;
    }

    public function with(ElearningCourseOutlineService $outlineService): array
    {
        $student = auth()->user();
        $classroomId = $student->latestProfile?->profileable?->classroom_id;
        $subject = Subject::findOrFail($this->subjectId);

        [$activeYear, $activeSemester] = $outlineService->getActiveAcademicYearAndSemester();

        $outline = $outlineService->getSubjectOutlineForStudent(
            $student,
            $this->subjectId,
            $classroomId ?? 0,
            $activeYear?->id ?? 0,
            $activeSemester
        );

        return [
            'subject' => $subject,
            'activeYear' => $activeYear,
            'activeSemester' => $activeSemester,
            'outline' => $outline,
        ];
    }
}; ?>

<div class="p-6 space-y-6 max-w-5xl mx-auto">
    {{-- Top Navigation & Header --}}
    <div>
        <a href="{{ route('student.materials') }}" wire:navigate class="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:text-emerald-600 mb-3 font-medium">
            <x-ui.icon name="o-arrow-left" class="w-4 h-4" />
            <span>Kembali ke Daftar Mata Pelajaran</span>
        </a>

        <div class="bg-gradient-to-r from-emerald-600 to-teal-700 rounded-2xl p-6 text-white shadow-lg space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <x-ui.badge :label="'Semester ' . $activeSemester" class="bg-white/20 text-white border-0 font-medium mb-2" size="xs" />
                    <h1 class="text-2xl font-bold">{{ $subject->name }}</h1>
                    <p class="text-xs text-emerald-100 mt-1">Struktur Modul & Kurikulum Pembelajaran Berurutan</p>
                </div>

                <div class="bg-white/10 backdrop-blur-md rounded-xl p-4 text-right min-w-[140px]">
                    <div class="text-2xl font-black">
                        {{ $outline['total_items'] > 0 ? round(($outline['completed_items'] / $outline['total_items']) * 100) : 0 }}%
                    </div>
                    <div class="text-xs text-emerald-100">Progres Selesai</div>
                </div>
            </div>

            {{-- Global Progress bar --}}
            <div class="w-full bg-black/20 h-2 rounded-full overflow-hidden">
                <div class="bg-white h-full rounded-full transition-all duration-500" style="width: {{ $outline['total_items'] > 0 ? round(($outline['completed_items'] / $outline['total_items']) * 100) : 0 }}%"></div>
            </div>
        </div>
    </div>

    {{-- SECTION 1: Buku Pegangan Utama (Handbook) --}}
    @if(count($outline['handbooks']) > 0)
        <div class="space-y-3">
            <h3 class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-2">
                <x-ui.icon name="o-book-open" class="w-4 h-4 text-emerald-600" />
                Buku Pegangan Utama
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach($outline['handbooks'] as $handbook)
                    <a href="{{ route('student.material-detail', $handbook['id']) }}" wire:navigate class="block">
                        <div class="p-4 rounded-xl border border-amber-200 dark:border-amber-900/40 bg-amber-50/50 dark:bg-amber-950/20 hover:border-amber-300 transition-all flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300">
                                    <x-ui.icon name="o-document-text" class="w-5 h-5" />
                                </div>
                                <div>
                                    <h4 class="font-semibold text-sm text-slate-900 dark:text-white">{{ $handbook['title'] }}</h4>
                                    <span class="text-xs text-amber-700 dark:text-amber-400 font-medium">Buku Pegangan / Referensi</span>
                                </div>
                            </div>
                            <x-ui.icon name="o-chevron-right" class="w-4 h-4 text-slate-400" />
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- SECTION 2: Bab / Modul Berurutan --}}
    <div class="space-y-4">
        <h3 class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-2">
            <x-ui.icon name="o-squares-2x2" class="w-4 h-4 text-emerald-600" />
            Daftar Modul & Kuis Bab
        </h3>

        @forelse($outline['chapters'] as $chapter)
            <div class="border border-slate-200 dark:border-slate-800 rounded-2xl bg-white dark:bg-slate-900 overflow-hidden shadow-sm transition-all {{ $chapter['is_locked'] ? 'opacity-60 bg-slate-50 dark:bg-slate-900/40' : '' }}">
                {{-- Chapter Header --}}
                <div class="p-4 bg-slate-50/80 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl font-black text-sm flex items-center justify-center {{ $chapter['is_completed'] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : ($chapter['is_locked'] ? 'bg-slate-200 text-slate-500 dark:bg-slate-800' : 'bg-emerald-600 text-white') }}">
                            @if($chapter['is_completed'])
                                <x-ui.icon name="o-check" class="w-5 h-5" />
                            @elseif($chapter['is_locked'])
                                <x-ui.icon name="o-lock-closed" class="w-4 h-4" />
                            @else
                                {{ $chapter['order'] }}
                            @endif
                        </div>

                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-slate-900 dark:text-white text-base">
                                    Bab {{ $chapter['order'] }}: {{ $chapter['title'] }}
                                </h3>
                                @if($chapter['is_locked'])
                                    <x-ui.badge label="Terkunci" variant="neutral" flat size="xs" />
                                @elseif($chapter['is_completed'])
                                    <x-ui.badge label="Modul Selesai" variant="success" flat size="xs" />
                                @endif
                            </div>
                            @if($chapter['description'])
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $chapter['description'] }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Items in Chapter (Materials + Quizzes) --}}
                <div class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    {{-- Materials --}}
                    @foreach($chapter['materials'] as $mat)
                        @if($mat['is_locked'])
                            <div class="p-3.5 px-5 flex items-center justify-between bg-slate-50/40 dark:bg-slate-900/20 text-slate-400 cursor-not-allowed">
                                <div class="flex items-center gap-3">
                                    <x-ui.icon name="o-lock-closed" class="w-4 h-4 text-slate-400" />
                                    <span class="text-sm font-medium">{{ $mat['title'] }}</span>
                                </div>
                                <span class="text-xs text-slate-400 font-medium">Selesaikan bab sebelumnya</span>
                            </div>
                        @else
                            <a href="{{ route('student.material-detail', $mat['id']) }}" wire:navigate class="p-3.5 px-5 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors group">
                                <div class="flex items-center gap-3">
                                    @if($mat['is_completed'])
                                        <x-ui.icon name="o-check-circle" class="w-5 h-5 text-emerald-500 shrink-0" />
                                    @else
                                        <div class="w-5 h-5 rounded-full border-2 border-slate-300 dark:border-slate-600 shrink-0"></div>
                                    @endif

                                    <div>
                                        <h4 class="text-sm font-semibold text-slate-800 dark:text-slate-200 group-hover:text-emerald-600 transition-colors">
                                            {{ $mat['title'] }}
                                        </h4>
                                        <div class="flex items-center gap-2 text-xs text-slate-400 mt-0.5">
                                            @if($mat['type'] === 'video')
                                                <span class="text-rose-600 font-medium flex items-center gap-1"><x-ui.icon name="o-play" class="w-3 h-3" /> Video & Transkrip</span>
                                            @elseif($mat['type'] === 'slides')
                                                <span class="text-blue-600 font-medium flex items-center gap-1"><x-ui.icon name="o-document-text" class="w-3 h-3" /> Presentation / PDF</span>
                                            @else
                                                <span class="text-slate-500 flex items-center gap-1"><x-ui.icon name="o-document" class="w-3 h-3" /> Artikel Teks</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    @if($mat['is_completed'])
                                        <x-ui.badge label="Sudah Dibaca" variant="success" flat size="xs" />
                                    @else
                                        <span class="text-xs font-semibold text-emerald-600 group-hover:translate-x-0.5 transition-transform flex items-center gap-1">
                                            Baca <x-ui.icon name="o-arrow-right" class="w-3 h-3" />
                                        </span>
                                    @endif
                                </div>
                            </a>
                        @endif
                    @endforeach

                    {{-- Quizzes --}}
                    @foreach($chapter['quizzes'] as $quiz)
                        @if($quiz['is_locked'])
                            <div class="p-3.5 px-5 flex items-center justify-between bg-amber-50/20 dark:bg-amber-950/10 text-slate-400 cursor-not-allowed">
                                <div class="flex items-center gap-3">
                                    <x-ui.icon name="o-lock-closed" class="w-4 h-4 text-amber-500/70" />
                                    <span class="text-sm font-semibold text-slate-500 dark:text-slate-400">{{ $quiz['title'] }}</span>
                                </div>
                                <span class="text-xs text-amber-700/70 dark:text-amber-400/70 font-medium">Baca semua materi bab ini dulu</span>
                            </div>
                        @else
                            <a href="{{ route('student.take-exam', $quiz['id']) }}" wire:navigate class="p-3.5 px-5 flex items-center justify-between bg-amber-50/40 dark:bg-amber-950/20 hover:bg-amber-50 dark:hover:bg-amber-950/40 transition-colors group">
                                <div class="flex items-center gap-3">
                                    @if($quiz['is_completed'])
                                        <x-ui.icon name="o-check-circle" class="w-5 h-5 text-emerald-500 shrink-0" />
                                    @else
                                        <x-ui.icon name="o-question-mark-circle" class="w-5 h-5 text-amber-500 shrink-0" />
                                    @endif

                                    <div>
                                        <h4 class="text-sm font-bold text-amber-900 dark:text-amber-200 group-hover:text-amber-600 transition-colors">
                                            {{ $quiz['title'] }}
                                        </h4>
                                        <span class="text-xs text-amber-700 dark:text-amber-400 font-medium">Kuis Evaluasi Bab {{ $chapter['order'] }}</span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    @if($quiz['is_completed'])
                                        <x-ui.badge label="Kuis Dikerjakan" variant="success" flat size="xs" />
                                    @else
                                        <x-ui.button label="Kerjakan Kuis" class="btn-warning btn-xs" />
                                    @endif
                                </div>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        @empty
            <div class="text-center py-10 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-dashed border-slate-200">
                <p class="text-xs text-slate-500">Belum ada modul bab yang dibuat untuk mata pelajaran ini.</p>
            </div>
        @endforelse
    </div>

    {{-- SECTION 3: Ujian Tengah & Akhir Semester --}}
    @if(count($outline['term_exams']) > 0)
        <div class="space-y-3 pt-4 border-t border-slate-200 dark:border-slate-800">
            <h3 class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-2">
                <x-ui.icon name="o-academic-cap" class="w-4 h-4 text-emerald-600" />
                Ujian Evaluasi Semester (UTS / UAS)
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($outline['term_exams'] as $termExam)
                    @if($termExam['is_locked'])
                        <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/40 opacity-70 flex items-center justify-between cursor-not-allowed">
                            <div class="flex items-center gap-3">
                                <x-ui.icon name="o-lock-closed" class="w-6 h-6 text-slate-400" />
                                <div>
                                    <h4 class="font-bold text-sm text-slate-600 dark:text-slate-400">{{ $termExam['title'] }}</h4>
                                    <p class="text-xs text-slate-400">Terbuka setelah seluruh bab & kuis selesai</p>
                                </div>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('student.take-exam', $termExam['id']) }}" wire:navigate class="block">
                            <div class="p-4 rounded-xl border-2 border-emerald-500 bg-emerald-50/30 dark:bg-emerald-950/20 hover:bg-emerald-50/60 transition-all flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="p-2.5 rounded-lg bg-emerald-600 text-white">
                                        <x-ui.icon name="o-academic-cap" class="w-6 h-6" />
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-sm text-slate-900 dark:text-white">{{ $termExam['title'] }}</h4>
                                        <span class="text-xs text-emerald-700 dark:text-emerald-400 font-semibold uppercase">{{ $termExam['exam_type'] === 'midterm' ? 'Ujian Tengah Semester' : 'Ujian Akhir Semester' }}</span>
                                    </div>
                                </div>

                                @if($termExam['is_completed'])
                                    <x-ui.badge label="Sudah Ujian" variant="success" size="sm" />
                                @else
                                    <x-ui.button label="Mulai Ujian" class="btn-primary btn-sm" />
                                @endif
                            </div>
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
</div>
