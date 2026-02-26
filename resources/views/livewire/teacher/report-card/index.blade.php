<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ReportCard;
use App\Traits\Assessments\HandlesReportCardGeneration;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.teacher.layouts.app')] class extends Component {
    use HandlesReportCardGeneration;

    public function mount(): void
    {
        $this->mountHandlesReportCardGeneration();
    }

    // Role-Specific Access Control
    protected function ensureAccessToClassroom(int $classroomId): void
    {
         if (!auth()->user()->hasAccessToClassroom($classroomId)) {
             abort(403, 'Anda tidak memiliki akses ke kelas ini.');
         }
    }

    // Role-Specific Dropdown Data
    protected function getAllowedClassrooms()
    {
        $assignedIds = auth()->user()->getAssignedClassroomIds();
        return Classroom::whereIn('id', $assignedIds)
             ->when($this->academicYearId, fn($q) => $q->where('academic_year_id', $this->academicYearId))
             ->orderBy('name')
             ->get();
    }
}; ?>

@include('livewire.shared.assessments._partials.report-card-ui')
