<?php

use App\Models\TeacherAssignment;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

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

<div class="p-6 space-y-6">
    {{-- Header --}}
    <div>
        <flux:heading size="xl" level="1">{{ __('Dashboard Guru') }}</flux:heading>
        <flux:subheading>{{ __('Selamat datang, ') . auth()->user()->name }}</flux:subheading>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="p-6 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <flux:icon icon="building-office" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Kelas Diampu') }}</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $classroomCount }}</p>
                </div>
            </div>
        </div>

        <div class="p-6 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <flux:icon icon="book-open" class="w-6 h-6 text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Mata Pelajaran') }}</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $subjectCount }}</p>
                </div>
            </div>
        </div>

        <div class="p-6 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                    <flux:icon icon="clipboard-document-check" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                </div>
                <div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Total Penugasan') }}</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $assignments->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Assignments List --}}
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading size="lg">{{ __('Penugasan Saya') }}</flux:heading>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left border-collapse">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-6 py-3 font-medium text-zinc-700 dark:text-zinc-300">{{ __('Tahun Ajaran') }}</th>
                        <th class="px-6 py-3 font-medium text-zinc-700 dark:text-zinc-300">{{ __('Kelas') }}</th>
                        <th class="px-6 py-3 font-medium text-zinc-700 dark:text-zinc-300">{{ __('Mata Pelajaran') }}</th>
                        <th class="px-6 py-3 font-medium text-zinc-700 dark:text-zinc-300">{{ __('Tipe') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($assignments as $assignment)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                            <td class="px-6 py-4 text-zinc-900 dark:text-white">
                                {{ $assignment->academicYear->name }}
                                @if($assignment->academicYear->is_active)
                                    <flux:badge size="sm" color="green" class="ml-2">Aktif</flux:badge>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-zinc-900 dark:text-white">
                                {{ $assignment->classroom->name }}
                                <span class="text-xs text-zinc-500">({{ $assignment->classroom->level->name }})</span>
                            </td>
                            <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">
                                {{ $assignment->subject?->name ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <flux:badge size="sm" variant="outline">
                                    {{ match($assignment->type) {
                                        'class_teacher' => 'Guru Kelas',
                                        'subject_teacher' => 'Guru Mapel',
                                        'homeroom' => 'Wali Kelas',
                                        default => $assignment->type
                                    } }}
                                </flux:badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                <flux:icon icon="inbox" class="w-12 h-12 mx-auto mb-2 opacity-20" />
                                <p>{{ __('Belum ada penugasan.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('teacher.students.index') }}" wire:navigate class="p-4 bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:border-blue-500 dark:hover:border-blue-500 transition-colors">
            <flux:icon icon="users" class="w-8 h-8 text-blue-600 mb-2" />
            <p class="font-medium text-zinc-900 dark:text-white">{{ __('Daftar Siswa') }}</p>
            <p class="text-xs text-zinc-500">{{ __('Lihat siswa yang Anda ampu') }}</p>
        </a>

        <a href="{{ route('teacher.grades') }}" wire:navigate class="p-4 bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:border-green-500 dark:hover:border-green-500 transition-colors">
            <flux:icon icon="pencil-square" class="w-8 h-8 text-green-600 mb-2" />
            <p class="font-medium text-zinc-900 dark:text-white">{{ __('Input Nilai') }}</p>
            <p class="text-xs text-zinc-500">{{ __('Input penilaian siswa') }}</p>
        </a>

        <a href="{{ route('teacher.assessments.competency') }}" wire:navigate class="p-4 bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:border-purple-500 dark:hover:border-purple-500 transition-colors">
            <flux:icon icon="clipboard-document-check" class="w-8 h-8 text-purple-600 mb-2" />
            <p class="font-medium text-zinc-900 dark:text-white">{{ __('Penilaian Kompetensi') }}</p>
            <p class="text-xs text-zinc-500">{{ __('Input capaian kompetensi') }}</p>
        </a>

        <a href="{{ route('teacher.report-cards') }}" wire:navigate class="p-4 bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:border-orange-500 dark:hover:border-orange-500 transition-colors">
            <flux:icon icon="document-text" class="w-8 h-8 text-orange-600 mb-2" />
            <p class="font-medium text-zinc-900 dark:text-white">{{ __('Lihat Rapor') }}</p>
            <p class="text-xs text-zinc-500">{{ __('Lihat rapor siswa') }}</p>
        </a>
    </div>
</div>
