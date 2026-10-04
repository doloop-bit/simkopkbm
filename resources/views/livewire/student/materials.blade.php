<?php

declare(strict_types=1);

use App\Services\ElearningCourseOutlineService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('components.layouts.student')] class extends Component {
    public string $search = '';

    #[Url]
    public ?string $semester = null;

    public function setSemester(string $semester): void
    {
        if (in_array($semester, ['Ganjil', 'Genap'], true)) {
            $this->semester = $semester;
        }
    }

    public function with(ElearningCourseOutlineService $outlineService): array
    {
        $student = auth()->user();
        [$activeYear, $activeSemester] = $outlineService->getActiveAcademicYearAndSemester();
        $selectedSemester = $this->semester ?? $activeSemester;

        $subjectsSummary = $outlineService->getStudentSubjectsSummary($student, $selectedSemester);

        if ($this->search) {
            $subjectsSummary = array_filter($subjectsSummary, function ($item) {
                return str_contains(strtolower($item['subject']->name), strtolower($this->search));
            });
        }

        return [
            'activeYear' => $activeYear,
            'activeSemester' => $activeSemester,
            'selectedSemester' => $selectedSemester,
            'subjectsSummary' => $subjectsSummary,
        ];
    }
}; ?>

<div class="p-6 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <x-ui.header 
            :title="__('Materi Pembelajaran')" 
            :subtitle="__('Daftar mata pelajaran untuk Semester ' . $selectedSemester . ' - ' . ($activeYear?->name ?? 'Tahun Ajaran Aktif'))" 
        />

        {{-- Switcher Semester Siswa --}}
        <div class="inline-flex p-1 bg-slate-100 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs self-start sm:self-auto">
            <button
                type="button"
                wire:click="setSemester('Ganjil')"
                class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all flex items-center gap-1.5 {{ $selectedSemester === 'Ganjil' ? 'bg-white dark:bg-slate-700 text-emerald-600 dark:text-emerald-400 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
            >
                <span>{{ __('Semester Ganjil') }}</span>
                @if($activeSemester === 'Ganjil')
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                @endif
            </button>

            <button
                type="button"
                wire:click="setSemester('Genap')"
                class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all flex items-center gap-1.5 {{ $selectedSemester === 'Genap' ? 'bg-white dark:bg-slate-700 text-emerald-600 dark:text-emerald-400 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
            >
                <span>{{ __('Semester Genap') }}</span>
                @if($activeSemester === 'Genap')
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                @endif
            </button>
        </div>
    </div>

    <x-ui.input wire:model.live.debounce.300ms="search" :placeholder="__('Cari mata pelajaran...')" icon="o-magnifying-glass" />

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($subjectsSummary as $item)
            @php
                $subject = $item['subject'];
                $progress = $item['progress_percentage'];
            @endphp
            <a href="{{ route('student.subject-outline', ['subjectId' => $subject->id, 'semester' => $selectedSemester]) }}" wire:navigate class="block group">
                <x-ui.card shadow class="hover:shadow-xl transition-all duration-200 border border-slate-100 dark:border-slate-800 h-full flex flex-col justify-between">
                    <div class="space-y-4">
                        <div class="flex items-start justify-between">
                            <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 group-hover:scale-105 transition-transform">
                                <x-ui.icon name="o-book-open" class="w-8 h-8" />
                            </div>
                            <x-ui.badge :label="$progress . '% Selesai'" :variant="$progress === 100 ? 'success' : 'info'" flat />
                        </div>

                        <div>
                            <h3 class="font-bold text-lg text-slate-900 dark:text-white group-hover:text-emerald-600 transition-colors">
                                {{ $subject->name }}
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                Kode: {{ $subject->code ?? '-' }} · Semester {{ $selectedSemester }}
                            </p>
                        </div>

                        {{-- Progress bar --}}
                        <div class="space-y-1">
                            <div class="flex justify-between text-xs text-slate-500">
                                <span>Progres belajar</span>
                                <span class="font-semibold">{{ $item['completed_items'] }} / {{ $item['total_items'] }} modul</span>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                                <div class="bg-emerald-500 h-full rounded-full transition-all duration-500" style="width: {{ $progress }}%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 mt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs font-semibold text-emerald-600 dark:text-emerald-400 group-hover:translate-x-1 transition-transform">
                        <span>Buka Materi Modul</span>
                        <x-ui.icon name="o-arrow-right" class="w-4 h-4" />
                    </div>
                </x-ui.card>
            </a>
        @empty
            <div class="col-span-full text-center py-16 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-dashed border-slate-200 dark:border-slate-700">
                <x-ui.icon name="o-academic-cap" class="w-16 h-16 mx-auto mb-3 text-slate-300 dark:text-slate-600" />
                <h4 class="font-semibold text-slate-700 dark:text-slate-300">Belum ada materi Semester {{ $selectedSemester }}</h4>
                <p class="text-xs text-slate-500 mt-1">Materi pelajaran untuk semester {{ $selectedSemester }} akan tampil di sini setelah diterbitkan guru.</p>
            </div>
        @endforelse
    </div>
</div>
