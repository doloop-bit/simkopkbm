<?php

declare(strict_types=1);

use App\Models\Subject;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public function with(): array
    {
        $teacher = auth()->user();
        
        $classroomIds = $teacher->getAssignedClassroomIds();
        $classrooms = \App\Models\Classroom::with('level')->whereIn('id', $classroomIds)->get();
        
        $phases = [];
        foreach ($classrooms as $c) {
            $phaseMap = $c->level->phase_map ?? [];
            if (isset($phaseMap[$c->class_level])) {
                $phases[] = $phaseMap[$c->class_level];
            }
        }
        $phases = array_unique($phases);

        return [
            'subjects' => Subject::whereIn('phase', $phases)->orderBy('name')->get(),
        ];
    }
}; ?>

<div class="p-6 space-y-6 text-slate-900 dark:text-white pb-24 md:pb-6">
    <x-ui.header :title="__('Mata Pelajaran')" :subtitle="__('Mata pelajaran pada jenjang kelas Anda')" separator />

    @if($subjects->isEmpty())
        <div class="py-12 text-center text-slate-400 italic text-sm">
            {{ __('Belum ada mata pelajaran yang ditugaskan kepada Anda.') }}
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($subjects as $subject)
                <x-ui.card shadow padding="sm" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                    <div class="flex items-center gap-4 p-2">
                        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 shrink-0">
                            <x-ui.icon name="o-book-open" class="w-6 h-6" />
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="font-bold text-lg text-slate-900 dark:text-white truncate">{{ $subject->name }}</span>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-xs font-mono text-slate-500">{{ $subject->code ?? '-' }}</span>
                                @if($subject->phase)
                                    <x-ui.badge :label="'Fase ' . $subject->phase" class="bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 text-[9px] font-black" />
                                @endif
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            @endforeach
        </div>
    @endif
</div>
