<?php

declare(strict_types=1);

use App\Traits\Assessments\HandlesDailyAttendance;
use App\Models\Classroom;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.app')] class extends Component {
    use HandlesDailyAttendance;

    public function mount(): void
    {
        $this->mountHandlesDailyAttendance();
    }

    protected function ensureAccessToClassroom(int $classroomId): void
    {
        $user = auth()->user();
        
        if ($user->isAdmin()) {
            return;
        }

        if (!$user->hasAccessToClassroom($classroomId)) {
             abort(403, 'Anda tidak berhak mengakses kelas ini.');
        }
    }

    protected function getAllowedClassrooms()
    {
        $user = auth()->user();
        $isAdmin = $user->isAdmin();
        
        $query = Classroom::query();

        if (!$isAdmin) {
            $assignedIds = $user->getAssignedClassroomIds();
            $query->whereIn('id', $assignedIds);
        }

        return $query
             ->when($this->academic_year_id, fn($q) => $q->where('academic_year_id', $this->academic_year_id))
             ->orderBy('name')
             ->get();
    }
}; ?>

@include('livewire.shared.attendance._partials.daily-ui')
