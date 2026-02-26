<?php

use Livewire\Component;
use App\Traits\Assessments\HandlesExtracurricularAssessment;
use Livewire\Attributes\Layout;

new #[Layout('components.admin.layouts.app')] class extends Component {
    use HandlesExtracurricularAssessment;

    public function mount()
    {
        $this->mountHandlesExtracurricularAssessment();
    }
}; ?>

@include('livewire.shared.assessments._partials.extracurricular-ui')
