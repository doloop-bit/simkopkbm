<?php

use App\Models\TeacherAssignment;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public function with(): array
    {
        $teacher = auth()->user();
        
        $assignments = TeacherAssignment::where('teacher_id', $teacher->id)
            ->with(['classroom.level', 'subject', 'academicYear'])
            ->get();

        return [
            'assignments' => $assignments,
        ];
    }
}; ?>

<div class="p-4 md:p-6 space-y-4 md:space-y-6">
    {{-- Header --}}
    <x-ui.header :title="__('Dashboard Guru')" :subtitle="__('Selamat datang, :name', ['name' => auth()->user()->name])" class="mb-0 sm:mb-6" />



    {{-- Quick Actions --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
        {{-- Daftar Siswa --}}
        <a href="{{ route('teacher.students.index') }}" wire:navigate class="group">
            <div class="h-full flex flex-col items-center text-center p-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 hover:border-blue-500/30">
                <div class="size-12 rounded-xl bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                    <x-ui.icon name="o-users" class="size-6" />
                </div>
                <h3 class="font-bold text-slate-800 dark:text-slate-200 text-[11px] sm:text-sm leading-tight">{{ __('Daftar Siswa') }}</h3>
            </div>
        </a>

        {{-- Input Nilai --}}
        <a href="{{ route('teacher.assessments.grading') }}" wire:navigate class="group">
            <div class="h-full flex flex-col items-center text-center p-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 hover:border-emerald-500/30">
                <div class="size-12 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                    <x-ui.icon name="o-pencil-square" class="size-6" />
                </div>
                <h3 class="font-bold text-slate-800 dark:text-slate-200 text-[11px] sm:text-sm leading-tight">{{ __('Input Nilai') }}</h3>
            </div>
        </a>

        {{-- Lihat Rapor --}}
        <a href="{{ route('teacher.report-cards') }}" wire:navigate class="group col-span-2 sm:col-span-1">
            <div class="h-full flex flex-col sm:flex-col items-center text-center p-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 hover:border-amber-500/30">
                <div class="size-12 rounded-xl bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                    <x-ui.icon name="o-document-text" class="size-6" />
                </div>
                <h3 class="font-bold text-slate-800 dark:text-slate-200 text-[11px] sm:text-sm leading-tight">{{ __('Lihat Rapor') }}</h3>
            </div>
        </a>
    </div>

    {{-- Assignments List --}}
    <div class="space-y-2">
        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">{{ __('Penugasan Saya') }}</p>
        <x-ui.card shadow sm border="none" class="bg-white/50 dark:bg-slate-900/50">
            <x-ui.table 
                :headers="[
                    ['key' => 'classroom', 'label' => __('Kelas')],
                    ['key' => 'subject', 'label' => __('Mapel')],
                    ['key' => 'type', 'label' => __('Tipe')]
                ]" 
                :rows="$assignments"
            >
            @scope('cell_classroom', $assignment)
                <div class="flex flex-col">
                    <span class="font-bold text-slate-900 dark:text-white text-xs">{{ $assignment->classroom->name }}</span>
                    <span class="text-[9px] uppercase font-bold text-slate-400 leading-tight">{{ $assignment->classroom->level->name }}</span>
                </div>
            @endscope

            @scope('cell_subject', $assignment)
                <span class="text-slate-600 dark:text-slate-400 text-xs">{{ $assignment->subject?->name ?? '-' }}</span>
            @endscope

            @scope('cell_type', $assignment)
                <span class="text-[9px] font-bold text-slate-500 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded uppercase">
                    {{ match($assignment->type) {
                        'class_teacher' => __('Kelas'),
                        'subject_teacher' => __('Mapel'),
                        'homeroom' => __('Wali'),
                        default => $assignment->type
                    } }}
                </span>
            @endscope
        </x-ui.table>
    </div>

        @if($assignments->isEmpty())
            <div class="py-8 text-center text-slate-400 flex flex-col items-center">
                <x-ui.icon name="o-inbox" class="size-12 mb-2 opacity-20" />
                <p class="italic text-xs">{{ __('Belum ada penugasan.') }}</p>
            </div>
        @endif
    </x-ui.card>
</div>
