<?php

use Livewire\Component;
use App\Traits\Assessments\HandlesGradingAssessment;
use Livewire\Attributes\Layout;

new #[Layout('components.admin.layouts.app')] class extends Component {
    use HandlesGradingAssessment;

    public function mount()
    {
        $this->mountHandlesGradingAssessment();
    }
}; ?>

@include('livewire.shared.assessments._partials.grading-ui')
