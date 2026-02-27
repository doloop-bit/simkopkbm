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

<div class="p-6 flex flex-col gap-6">
    {{-- Header --}}
    <x-header title="Dashboard Guru" subtitle="Selamat datang, {{ auth()->user()->name }}" separator />

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-stat
            title="Kelas Diampu"
            value="{{ $classroomCount }}"
            icon="o-building-office"
            class="bg-base-100 shadow-sm"
            color="text-primary"
        />

        <x-stat
            title="Mata Pelajaran"
            value="{{ $subjectCount }}"
            icon="o-book-open"
            class="bg-base-100 shadow-sm"
            color="text-success"
        />

        <x-stat
            title="Total Penugasan"
            value="{{ $assignments->count() }}"
            icon="o-clipboard-document-check"
            class="bg-base-100 shadow-sm"
            color="text-secondary"
        />
    </div>

    {{-- Assignments List --}}
    <x-card title="Penugasan Saya" separator shadow>
        <x-table :headers="[
            ['key' => 'academicYear.name', 'label' => 'Tahun Ajaran'],
            ['key' => 'classroom_name', 'label' => 'Kelas'],
            ['key' => 'subject.name', 'label' => 'Mata Pelajaran'],
            ['key' => 'type_label', 'label' => 'Tipe']
        ]" :rows="$assignments">
            @scope('cell_academicYear.name', $assignment)
                <div class="flex items-center gap-2">
                    {{ $assignment->academicYear->name }}
                    @if($assignment->academicYear->is_active)
                        <x-badge label="Aktif" class="badge-success badge-sm" />
                    @endif
                </div>
            @endscope

            @scope('cell_classroom_name', $assignment)
                <div class="flex flex-col">
                    <span class="font-bold">{{ $assignment->classroom->name }}</span>
                    <span class="text-[10px] uppercase opacity-60">{{ $assignment->classroom->level->name }}</span>
                </div>
            @endscope

            @scope('cell_subject.name', $assignment)
                {{ $assignment->subject?->name ?? '-' }}
            @endscope

            @scope('cell_type_label', $assignment)
                <x-badge 
                    :label="match($assignment->type) {
                        'class_teacher' => 'Guru Kelas',
                        'subject_teacher' => 'Guru Mapel',
                        'homeroom' => 'Wali Kelas',
                        default => $assignment->type
                    }" 
                    outline
                    class="badge-sm"
                />
            @endscope
        </x-table>

        @if($assignments->isEmpty())
            <div class="py-12 text-center opacity-30 flex flex-col items-center">
                <x-icon name="o-inbox" class="size-12 mb-2" />
                <p>Belum ada penugasan.</p>
            </div>
        @endif
    </x-card>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <a href="{{ route('teacher.students.index') }}" wire:navigate>
            <x-card class="hover:bg-base-200 transition-colors" shadow sm>
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-xl">
                        <x-icon name="o-users" class="size-8 text-primary" />
                    </div>
                    <div>
                        <p class="font-bold">Daftar Siswa</p>
                        <p class="text-[10px] opacity-60">Lihat siswa yang Anda ampu</p>
                    </div>
                </div>
            </x-card>
        </a>

        <a href="{{ route('teacher.assessments.grading') }}" wire:navigate>
            <x-card class="hover:bg-base-200 transition-colors" shadow sm>
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-success/10 rounded-xl">
                        <x-icon name="o-pencil-square" class="size-8 text-success" />
                    </div>
                    <div>
                        <p class="font-bold">Input Nilai & TP</p>
                        <p class="text-[10px] opacity-60">Input penilaian rapor dan TP</p>
                    </div>
                </div>
            </x-card>
        </a>

        <a href="{{ route('teacher.report-cards') }}" wire:navigate>
            <x-card class="hover:bg-base-200 transition-colors" shadow sm>
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-warning/10 rounded-xl">
                        <x-icon name="o-document-text" class="size-8 text-warning" />
                    </div>
                    <div>
                        <p class="font-bold">Lihat Rapor</p>
                        <p class="text-[10px] opacity-60">Lihat rapor siswa</p>
                    </div>
                </div>
            </x-card>
        </a>
    </div>
</div>
