<?php

declare(strict_types=1);

use App\Traits\Assessments\HandlesDiniyahAssessment;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.app')] class extends Component {
    use HandlesDiniyahAssessment;

    public function mount(): void
    {
        if (!auth()->user()->isGuru()) {
            abort(403);
        }

        $this->mountHandlesDiniyahAssessment();
    }
}; ?>

<div class="p-6 space-y-8 text-slate-900 dark:text-white pb-24 md:pb-6">
    @if (session('success'))
        <x-ui.alert :title="__('Sukses')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100" dismissible>
            {{ session('success') }}
        </x-ui.alert>
    @endif

    <x-ui.header :title="__('Penilaian Diniyah (Guru)')" :subtitle="__('Input nilai mata pelajaran diniyah untuk siswa di kelas binaan/pengampu.')" separator>
        @if($classroom_id && $diniyah_subject_id)
            <x-slot:actions>
                <x-ui.button :label="__('Simpan Semua Nilai')" icon="o-check" class="btn-primary shadow-lg shadow-primary/20" wire:click="save" spinner="save" />
            </x-slot:actions>
        @endif
    </x-ui.header>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <x-ui.select wire:model.live="academic_year_id" :label="__('Tahun Ajaran')" :options="$years" />
        <x-ui.select 
            wire:model.live="semester" 
            :label="__('Semester')" 
            :options="[
                ['id' => '1', 'name' => __('Semester 1')],
                ['id' => '2', 'name' => __('Semester 2')],
            ]" 
        />
        <x-ui.select 
            wire:model.live="classroom_id" 
            :label="__('Kelas / Rombel')" 
            :placeholder="__('Pilih Kelas')"
            :options="$classroomsKesetaraan"
        />
        <x-ui.select 
            wire:model.live="diniyah_subject_id" 
            :label="__('Mata Pelajaran Diniyah')" 
            :placeholder="__('Pilih Mata Pelajaran')"
            :options="$diniyahSubjects"
            :disabled="!$classroom_id"
        />
    </div>

    @include('livewire.shared.assessments._partials.diniyah-grading-ui')
</div>
