<?php

use App\Models\StudentProfile;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.teacher.layouts.app')] class extends Component {
    public function with(): array
    {
        $teacher = auth()->user();
        $assignedClassroomIds = $teacher->getAssignedClassroomIds();

        $students = StudentProfile::whereIn('classroom_id', $assignedClassroomIds)
            ->with(['profile.user', 'classroom.level', 'classroom.academicYear'])
            ->orderBy('classroom_id')
            ->get();

        return [
            'students' => $students,
            'assignedClassrooms' => $teacher->assignedClassrooms()->with('level', 'academicYear')->get(),
        ];
    }
}; ?>

<div class="p-6 space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Daftar Siswa') }}</flux:heading>
            <flux:subheading>{{ __('Siswa di kelas yang Anda ampu') }}</flux:subheading>
        </div>
    </div>

    {{-- Classroom Filter Info --}}
    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
        <div class="flex items-start gap-3">
            <flux:icon icon="information-circle" class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5" />
            <div>
                <p class="font-medium text-blue-900 dark:text-blue-200">{{ __('Kelas yang Anda Ampu:') }}</p>
                <div class="flex flex-wrap gap-2 mt-2">
                    @foreach($assignedClassrooms as $classroom)
                        <flux:badge color="blue">
                            {{ $classroom->name }} ({{ $classroom->academicYear->name }})
                        </flux:badge>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Students Table --}}
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left border-collapse">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-6 py-3 font-medium text-zinc-700 dark:text-zinc-300">{{ __('Nama Siswa') }}</th>
                        <th class="px-6 py-3 font-medium text-zinc-700 dark:text-zinc-300">{{ __('NIS / NISN') }}</th>
                        <th class="px-6 py-3 font-medium text-zinc-700 dark:text-zinc-300">{{ __('Kelas') }}</th>
                        <th class="px-6 py-3 font-medium text-zinc-700 dark:text-zinc-300">{{ __('Jenjang') }}</th>
                        <th class="px-6 py-3 font-medium text-zinc-700 dark:text-zinc-300">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($students as $student)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                        <span class="text-sm font-medium text-blue-600 dark:text-blue-400">
                                            {{ $student->profile->user->initials() }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-zinc-900 dark:text-white">{{ $student->profile->user->name }}</p>
                                        <p class="text-xs text-zinc-500">{{ $student->profile->user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">
                                <div>
                                    <p>{{ $student->nis ?? '-' }}</p>
                                    <p class="text-xs text-zinc-500">{{ $student->nisn ?? '-' }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-zinc-900 dark:text-white">
                                {{ $student->classroom->name }}
                            </td>
                            <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">
                                {{ $student->classroom->level->name }}
                            </td>
                            <td class="px-6 py-4">
                                <flux:badge size="sm" :color="$student->profile->user->is_active ? 'green' : 'zinc'">
                                    {{ $student->profile->user->is_active ? 'Aktif' : 'Tidak Aktif' }}
                                </flux:badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                <flux:icon icon="users" class="w-12 h-12 mx-auto mb-2 opacity-20" />
                                <p>{{ __('Belum ada siswa di kelas yang Anda ampu.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($students->isNotEmpty())
            <div class="px-6 py-4 bg-zinc-50 dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-700">
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('Total: ') }} <span class="font-medium text-zinc-900 dark:text-white">{{ $students->count() }}</span> {{ __('siswa') }}
                </p>
            </div>
        @endif
    </div>
</div>
