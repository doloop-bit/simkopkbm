<?php

use App\Models\TeacherAssignment;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.teacher.layouts.app')] class extends Component {
    public function with(): array
    {
        $teacher = auth()->user();
        
        $assignments = TeacherAssignment::where('teacher_id', $teacher->id)
            ->with(['classroom.level', 'subject', 'academicYear'])
            ->get();

        $classroomCount = $assignments->pluck('classroom_id')->unique()->count();
        $subjectCount = $assignments->whereNotNull('subject_id')->pluck('subject_id')->unique()->count();

        return [
            'assignments' => $assignments,
            'classroomCount' => $classroomCount,
            'subjectCount' => $subjectCount,
        ];
    }
}; ?>

<div class="p-6 space-y-8">
    {{-- Header --}}
    <x-ui.header :title="__('Dashboard Guru')" :subtitle="__('Selamat datang, :name', ['name' => auth()->user()->name])" separator />

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <x-ui.stat
            :title="__('Kelas Diampu')"
            :value="$classroomCount"
            icon="o-building-office"
            color="text-blue-600 dark:text-blue-400"
        />

        <x-ui.stat
            :title="__('Mata Pelajaran')"
            :value="$subjectCount"
            icon="o-book-open"
            color="text-emerald-600 dark:text-emerald-400"
        />

        <x-ui.stat
            :title="__('Total Penugasan')"
            :value="$assignments->count()"
            icon="o-clipboard-document-check"
            color="text-indigo-600 dark:text-indigo-400"
        />
    </div>

    {{-- Assignments List --}}
    <x-ui.card :title="__('Penugasan Saya')" separator shadow>
        <x-ui.table 
            :headers="[
                ['key' => 'academic_year', 'label' => __('Tahun Ajaran')],
                ['key' => 'classroom', 'label' => __('Kelas')],
                ['key' => 'subject', 'label' => __('Mata Pelajaran')],
                ['key' => 'type', 'label' => __('Tipe')]
            ]" 
            :rows="$assignments"
        >
            @scope('cell_academic_year', $assignment)
                <div class="flex items-center gap-2">
                    <span class="font-medium text-slate-700 dark:text-slate-300">{{ $assignment->academicYear->name }}</span>
                    @if($assignment->academicYear->is_active)
                        <x-ui.badge :label="__('Aktif')" class="bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 text-[10px]" />
                    @endif
                </div>
            @endscope

            @scope('cell_classroom', $assignment)
                <div class="flex flex-col">
                    <span class="font-bold text-slate-900 dark:text-white">{{ $assignment->classroom->name }}</span>
                    <span class="text-[10px] uppercase font-bold text-slate-400 leading-tight">{{ $assignment->classroom->level->name }}</span>
                </div>
            @endscope

            @scope('cell_subject', $assignment)
                <span class="text-slate-600 dark:text-slate-400">{{ $assignment->subject?->name ?? '-' }}</span>
            @endscope

            @scope('cell_type', $assignment)
                <x-ui.badge 
                    :label="match($assignment->type) {
                        'class_teacher' => __('Guru Kelas'),
                        'subject_teacher' => __('Guru Mapel'),
                        'homeroom' => __('Wali Kelas'),
                        default => $assignment->type
                    }" 
                    class="bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700 text-[10px]"
                />
            @endscope
        </x-ui.table>

        @if($assignments->isEmpty())
            <div class="py-16 text-center text-slate-400 flex flex-col items-center">
                <x-ui.icon name="o-inbox" class="size-16 mb-4 opacity-20" />
                <p class="italic">{{ __('Belum ada penugasan.') }}</p>
            </div>
        @endif
    </x-ui.card>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <a href="{{ route('teacher.students.index') }}" wire:navigate class="group">
            <x-ui.card class="hover:border-primary/50 hover:shadow-xl hover:shadow-primary/5 transition-all duration-300" shadow sm padding>
                <div class="flex items-center gap-4">
                    <div class="p-3.5 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-2xl group-hover:scale-105 transition-transform">
                        <x-ui.icon name="o-users" class="size-8" />
                    </div>
                    <div>
                        <p class="font-bold text-slate-900 dark:text-white leading-tight">{{ __('Daftar Siswa') }}</p>
                        <p class="text-xs text-slate-500 mt-1">{{ __('Lihat siswa yang Anda ampu') }}</p>
                    </div>
                </div>
            </x-ui.card>
        </a>

        <a href="{{ route('teacher.assessments.grading') }}" wire:navigate class="group">
            <x-ui.card class="hover:border-emerald-500/50 hover:shadow-xl hover:shadow-emerald-500/5 transition-all duration-300" shadow sm padding>
                <div class="flex items-center gap-4">
                    <div class="p-3.5 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 rounded-2xl group-hover:scale-105 transition-transform">
                        <x-ui.icon name="o-pencil-square" class="size-8" />
                    </div>
                    <div>
                        <p class="font-bold text-slate-900 dark:text-white leading-tight">{{ __('Input Nilai & TP') }}</p>
                        <p class="text-xs text-slate-500 mt-1">{{ __('Input penilaian rapor dan TP') }}</p>
                    </div>
                </div>
            </x-ui.card>
        </a>

        <a href="{{ route('teacher.report-cards') }}" wire:navigate class="group">
            <x-ui.card class="hover:border-amber-500/50 hover:shadow-xl hover:shadow-amber-500/5 transition-all duration-300" shadow sm padding>
                <div class="flex items-center gap-4">
                    <div class="p-3.5 bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 rounded-2xl group-hover:scale-105 transition-transform">
                        <x-ui.icon name="o-document-text" class="size-8" />
                    </div>
                    <div>
                        <p class="font-bold text-slate-900 dark:text-white leading-tight">{{ __('Lihat Rapor') }}</p>
                        <p class="text-xs text-slate-500 mt-1">{{ __('Lihat rapor siswa') }}</p>
                    </div>
                </div>
            </x-ui.card>
        </a>
    </div>
</div>
