<?php

declare(strict_types=1);

use App\Traits\Assessments\HandlesDailyAttendance;
use App\Models\Classroom;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.admin.layouts.app')] class extends Component {
    use HandlesDailyAttendance;

    public function mount(): void
    {
        $this->mountHandlesDailyAttendance();
    }

    protected function ensureAccessToClassroom(int $classroomId): void
    {
        // Admin has access to all
    }

    protected function getAllowedClassrooms()
    {
        return Classroom::query()
             ->when($this->academic_year_id, fn($q) => $q->where('academic_year_id', $this->academic_year_id))
             ->orderBy('name')
             ->get();
    }
}; ?>

@include('livewire.shared.attendance._partials.daily-ui')
