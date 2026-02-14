<?php

declare(strict_types=1);

use App\Traits\Assessments\HandlesDailyAttendance;
use App\Models\Classroom;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.teacher.layouts.app')] class extends Component {
    use HandlesDailyAttendance;

    public function mount(): void
    {
        $this->mountHandlesDailyAttendance();
    }

    protected function ensureAccessToClassroom(int $classroomId): void
    {
        if (!auth()->user()->hasAccessToClassroom($classroomId)) {
             abort(403, 'Anda tidak berhak mengakses kelas ini.');
        }
    }

    protected function getAllowedClassrooms()
    {
        $assignedIds = auth()->user()->getAssignedClassroomIds();
        return Classroom::whereIn('id', $assignedIds)
             ->when($this->academic_year_id, fn($q) => $q->where('academic_year_id', $this->academic_year_id))
             ->orderBy('name')
             ->get();
    }
}; ?>

@include('livewire.shared.attendance._partials.daily-ui')
