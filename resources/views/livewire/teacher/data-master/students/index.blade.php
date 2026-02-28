<?php

use App\Models\StudentProfile;
use Livewire\Attributes\Layout;
use Livewire\Component;

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

<div class="p-6 space-y-6 text-slate-900 dark:text-white pb-24 md:pb-6">
    {{-- Header --}}
    <x-ui.header :title="__('Daftar Siswa')" :subtitle="__('Siswa di kelas yang Anda ampu')" separator />

    {{-- Classroom Filter Info --}}
    <x-ui.alert icon="o-information-circle" class="bg-blue-50 text-blue-800 border-blue-100 shadow-sm">
        <div class="flex flex-col gap-2">
            <p class="font-black text-[11px] uppercase tracking-widest">{{ __('Kelas yang Anda Ampu:') }}</p>
            <div class="flex flex-wrap gap-2">
                @foreach($assignedClassrooms as $classroom)
                    <x-ui.badge class="bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 text-[10px] font-black border-none ring-1 ring-blue-200 dark:ring-blue-800">
                        {{ $classroom->name }} ({{ $classroom->academicYear->name }})
                    </x-ui.badge>
                @endforeach
            </div>
        </div>
    </x-ui.alert>

    {{-- Students Table --}}
    <x-ui.card shadow padding="false">
        <x-ui.table 
            :headers="[
                ['key' => 'name', 'label' => __('Nama Siswa')],
                ['key' => 'nis', 'label' => __('NIS / NISN')],
                ['key' => 'classroom_name', 'label' => __('Kelas')],
                ['key' => 'level_name', 'label' => __('Jenjang')],
                ['key' => 'status', 'label' => __('Status')]
            ]" 
            :rows="$students"
        >
            @scope('cell_name', $student)
                <div class="flex items-center gap-3">
                    <x-ui.avatar 
                        :image="($student->photo && Storage::disk('public')->exists($student->photo)) ? '/storage/'.$student->photo : null" 
                        fallback="o-user" 
                        class="!w-10 !h-10 rounded-lg shadow-sm"
                    />
                    <div class="flex flex-col">
                        <span class="font-bold text-slate-900 dark:text-white">{{ $student->profile->user->name }}</span>
                        <span class="text-[10px] text-slate-400 font-mono tracking-tighter">{{ $student->profile->user->email }}</span>
                    </div>
                </div>
            @endscope

            @scope('cell_nis', $student)
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300 font-mono italic">{{ $student->nis ?? '-' }}</span>
                    <span class="text-[10px] uppercase font-bold text-slate-400 tracking-tight">NISN: {{ $student->nisn ?? '-' }}</span>
                </div>
            @endscope

            @scope('cell_classroom_name', $student)
                <x-ui.badge :label="$student->classroom->name" class="bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 text-[10px] font-black" />
            @endscope

            @scope('cell_level_name', $student)
                <span class="text-xs text-slate-500 font-medium">{{ $student->classroom->level->name }}</span>
            @endscope

            @scope('cell_status', $student)
                @if($student->profile->user->is_active)
                    <x-ui.badge :label="__('Aktif')" class="bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 text-[10px] font-black" />
                @else
                    <x-ui.badge :label="__('Non-Aktif')" class="bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-500 text-[10px] font-black" />
                @endif
            @endscope
        </x-ui.table>

        @if($students->isEmpty())
            <div class="py-12 text-center text-slate-400 italic text-sm">
                {{ __('Belum ada siswa di kelas yang Anda ampu.') }}
            </div>
        @else
            <div class="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex justify-end">
                <div class="text-[10px] font-black uppercase text-slate-400 tracking-widest">
                    {{ __('Total:') }} <span class="text-slate-900 dark:text-white">{{ $students->count() }}</span> {{ __('siswa') }}
                </div>
            </div>
        @endif
    </x-ui.card>
</div>
