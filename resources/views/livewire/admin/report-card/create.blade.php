<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ReportCard;
use App\Traits\Assessments\HandlesReportCardGeneration;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.admin.layouts.app')] class extends Component {
    use HandlesReportCardGeneration;

    public function mount(): void
    {
        $this->mountHandlesReportCardGeneration();
    }

    // Admin has access to all classrooms
    protected function ensureAccessToClassroom(int $classroomId): void
    {
        // No restriction for admin
    }

    // Admin sees all classrooms
    protected function getAllowedClassrooms()
    {
        return Classroom::query()
             ->when($this->academicYearId, fn($q) => $q->where('academic_year_id', $this->academicYearId))
             ->orderBy('name')
             ->get();
    }
    
    // Override preview teacher to ensure it shows the actual teacher, not admin if admin is previewing?
    // Actually, report card usually signed by teacher. Trait defaults to auth()->user().
    // If admin generates, they might be signing it? Or should it be the class teacher?
    // Let's better fetch the class teacher.
    protected function getTeacherForPreview($reportCard) {
        // Try to find the main teacher for the class
        return $reportCard->classroom->homeroomTeacher ?? auth()->user();
    }
}; ?>

@include('livewire.shared.assessments._partials.report-card-ui')
