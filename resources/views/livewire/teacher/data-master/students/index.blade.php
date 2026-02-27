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

<div class="p-6 flex flex-col gap-6">
    {{-- Header --}}
    <x-header title="Daftar Siswa" subtitle="Siswa di kelas yang Anda ampu" separator />

    {{-- Classroom Filter Info --}}
    <div class="p-4 bg-primary/5 border border-primary/20 rounded-xl">
        <div class="flex items-start gap-3">
            <x-icon name="o-information-circle" class="size-5 text-primary mt-0.5" />
            <div>
                <p class="font-bold text-sm text-primary">{{ __('Kelas yang Anda Ampu:') }}</p>
                <div class="flex flex-wrap gap-2 mt-2">
                    @foreach($assignedClassrooms as $classroom)
                        <x-badge color="primary" class="badge-sm" outline>
                            {{ $classroom->name }} ({{ $classroom->academicYear->name }})
                        </x-badge>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Students Table --}}
    <x-card shadow>
        <x-table :headers="[
            ['key' => 'name', 'label' => 'Nama Siswa'],
            ['key' => 'nis', 'label' => 'NIS / NISN'],
            ['key' => 'classroom.name', 'label' => 'Kelas'],
            ['key' => 'classroom.level.name', 'label' => 'Jenjang'],
            ['key' => 'status', 'label' => 'Status']
        ]" :rows="$students" striped>
            @scope('cell_name', $student)
                <div class="flex items-center gap-3">
                    <div class="avatar placeholder">
                        <div class="bg-primary/10 text-primary rounded-full w-10">
                            <span class="text-sm font-bold">{{ $student->profile->user->initials() }}</span>
                        </div>
                    </div>
                    <div class="flex flex-col">
                        <span class="font-bold">{{ $student->profile->user->name }}</span>
                        <span class="text-[10px] opacity-60">{{ $student->profile->user->email }}</span>
                    </div>
                </div>
            @endscope

            @scope('cell_nis', $student)
                <div class="flex flex-col text-sm">
                    <span class="font-medium">{{ $student->nis ?? '-' }}</span>
                    <span class="text-[10px] opacity-50">{{ $student->nisn ?? '-' }}</span>
                </div>
            @endscope

            @scope('cell_status', $student)
                <x-badge 
                    :label="$student->profile->user->is_active ? 'Aktif' : 'Tidak Aktif'" 
                    class="{{ $student->profile->user->is_active ? 'badge-success' : 'badge-ghost' }} badge-sm" 
                />
            @endscope
        </x-table>

        @if($students->isEmpty())
            <div class="py-12 text-center opacity-30 flex flex-col items-center">
                <x-icon name="o-users" class="size-12 mb-2" />
                <p>Belum ada siswa di kelas yang Anda ampu.</p>
            </div>
        @else
            <x-slot:actions>
                <div class="text-sm opacity-60">
                    Total: <span class="font-bold">{{ $students->count() }}</span> siswa
                </div>
            </x-slot:actions>
        @endif
    </x-card>
</div>
