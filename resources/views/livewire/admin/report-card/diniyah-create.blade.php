<?php

declare(strict_types=1);

use App\Traits\Assessments\HandlesDiniyahReportCard;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.app')] class extends Component {
    use HandlesDiniyahReportCard;

    public function mount(): void
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $this->mountHandlesDiniyahReportCard();
    }
}; ?>

<div class="p-6 space-y-8 text-slate-900 dark:text-white pb-24 md:pb-6">
    <x-ui.header :title="__('Rapor Diniyah')" :subtitle="__('Generate dan cetak rapor khusus mata pelajaran diniyah / keagamaan.')" separator />

    @include('livewire.shared.assessments._partials.diniyah-report-card-ui')
</div>
