<?php

declare(strict_types=1);

use App\Models\ExtracurricularActivity;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public function with(): array
    {
        $teacher = auth()->user();
        
        // Get the levels of classrooms the teacher is assigned to
        $classroomIds = $teacher->getAssignedClassroomIds();
        $levelIds = \App\Models\Classroom::whereIn('id', $classroomIds)->pluck('level_id')->unique();

        return [
            'activities' => ExtracurricularActivity::with('level')
                ->whereIn('level_id', $levelIds)
                ->orderBy('name')
                ->paginate(15),
        ];
    }
}; ?>

<div class="p-6 space-y-6 text-slate-900 dark:text-white pb-24 md:pb-6">
    <x-ui.header :title="__('Ekstrakurikuler')" :subtitle="__('Daftar kegiatan ekstrakurikuler yang Anda bina')" separator />

    <x-ui.card shadow padding="false">
        <x-ui.table 
            :headers="[
                ['key' => 'name', 'label' => __('Nama Ekskul')],
                ['key' => 'level.name', 'label' => __('Jenjang')],
                ['key' => 'status', 'label' => __('Status')],
            ]" 
            :rows="$activities"
        >
            @scope('cell_name', $activity)
                <div class="flex flex-col">
                    <span class="font-bold text-slate-900 dark:text-white">{{ $activity->name }}</span>
                    <span class="text-xs text-slate-500">{{ $activity->instructor ?? '-' }}</span>
                </div>
            @endscope

            @scope('cell_status', $activity)
                @if($activity->is_active)
                    <x-ui.badge :label="__('Aktif')" class="bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 text-[10px] font-black" />
                @else
                    <x-ui.badge :label="__('Non-aktif')" class="bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400 text-[10px] font-black" />
                @endif
            @endscope
        </x-ui.table>

        @if($activities->isEmpty())
            <div class="py-12 text-center text-slate-400 italic text-sm">
                {{ __('Belum ada kegiatan ekstrakurikuler yang ditugaskan kepada Anda.') }}
            </div>
        @else
            <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                {{ $activities->links() }}
            </div>
        @endif
    </x-ui.card>
</div>
