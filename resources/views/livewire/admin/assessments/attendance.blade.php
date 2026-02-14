<?php

use Livewire\Volt\Component;
use App\Traits\Assessments\HandlesAttendanceAssessment;
use Livewire\Attributes\Layout;

new #[Layout('components.admin.layouts.app')] class extends Component {
    use HandlesAttendanceAssessment;

    public function mount()
    {
        $this->mountHandlesAttendanceAssessment();
    }
}; ?>

@include('livewire.shared.assessments._partials.attendance-ui')
