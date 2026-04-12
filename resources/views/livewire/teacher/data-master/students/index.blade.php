<?php

use App\Models\User;
use App\Traits\Shared\HandlesPeriodicRecord;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.app')] class extends Component {
    use HandlesPeriodicRecord;

    public function mount(): void
    {
        $this->mountHandlesPeriodicRecord();
    }

    public function with(): array
    {
        $teacher = auth()->user();
        $assignedClassroomIds = $teacher->getAssignedClassroomIds();

        $students = User::where('role', 'siswa')
            ->whereHas('latestProfile', fn($q) => $q->whereIn('classroom_id', $assignedClassroomIds))
            ->with(['latestProfile.profileable.classroom.level', 'latestProfile.profileable.classroom.academicYear'])
            ->orderBy('name')
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

    @if (session('success'))
        <x-ui.alert :title="__('Berhasil')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100" dismissible>
            {{ session('success') }}
        </x-ui.alert>
    @endif

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
    <x-ui.card shadow padding="false" class="overflow-hidden">
        <x-ui.table 
            :headers="[
                ['key' => 'name', 'label' => __('Siswa')],
                ['key' => 'nis', 'label' => __('NIS'), 'class' => 'hidden lg:table-cell'],
                ['key' => 'classroom_name', 'label' => __('Kelas'), 'class' => 'hidden sm:table-cell'],
                ['key' => 'status', 'label' => __('Status'), 'class' => 'hidden md:table-cell'],
                ['key' => 'actions', 'label' => '', 'class' => 'text-right w-16']
            ]" 
            :rows="$students"
        >
            @scope('cell_name', $student)
                @php $profile = $student->latestProfile?->profileable; @endphp
                <div class="flex items-center gap-3">
                    <x-ui.avatar 
                        :image="($profile?->photo && Storage::disk('public')->exists($profile->photo)) ? '/storage/'.$profile->photo : null" 
                        icon="o-user" 
                        class="!w-10 !h-10 rounded-xl shadow-sm hidden sm:grid flex-none"
                    />
                    <div class="flex flex-col">
                        <span class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm leading-tight">{{ $student->name }}</span>
                        <div class="flex items-center gap-1.5 mt-0.5">
                            <span class="text-[9px] text-slate-400 font-mono tracking-tighter">{{ $profile?->nisn ?? $profile?->nis }}</span>
                            <span class="sm:hidden text-[9px] bg-slate-100 dark:bg-slate-800 text-slate-500 px-1 rounded font-black uppercase">{{ $profile?->classroom->name }}</span>
                        </div>
                    </div>
                </div>
            @endscope

            @scope('cell_nis', $student)
                <span class="text-xs font-medium text-slate-600 dark:text-slate-400 font-mono">{{ $student->latestProfile?->profileable?->nis ?? '-' }}</span>
            @endscope

            @scope('cell_classroom_name', $student)
                <x-ui.badge :label="$student->latestProfile?->profileable?->classroom?->name" class="bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 text-[10px] font-black border-none" />
            @endscope

            @scope('cell_status', $student)
                <div class="flex items-center gap-1.5">
                    <div class="size-1.5 rounded-full {{ $student->is_active ? 'bg-emerald-500' : 'bg-slate-300' }}"></div>
                    <span class="text-[10px] uppercase font-black tracking-widest text-slate-500">{{ $student->is_active ? __('Aktif') : __('Non-Aktif') }}</span>
                </div>
            @endscope

            @scope('cell_actions', $student)
                <div class="flex justify-end pr-2">
                    <x-ui.button 
                        icon="o-chart-bar" 
                        wire:click="openPeriodic({{ $student->id }})" 
                        class="btn-sm btn-ghost hover:bg-primary/10 hover:text-primary transition-colors h-8 w-8" 
                    />
                </div>
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

    {{-- Periodic Data Modal --}}
    @include('livewire.admin.data-master.students.partials.periodic-modal', ['editing' => $editingUserForPeriodic])
</div>
