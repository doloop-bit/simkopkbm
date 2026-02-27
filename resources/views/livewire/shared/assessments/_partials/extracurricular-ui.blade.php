<div class="p-6 flex flex-col gap-6">
    <x-header title="Penilaian Ekstrakurikuler" subtitle="Input penilaian kegiatan ekstrakurikuler siswa." separator />

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <x-select wire:model.live="academic_year_id" label="Tahun Ajaran" :options="$years" :disabled="$isReadonly" />
        <x-select 
            wire:model.live="semester" 
            label="Semester" 
            :options="[
                ['id' => '1', 'name' => 'Semester 1'],
                ['id' => '2', 'name' => 'Semester 2'],
            ]" 
            :disabled="$isReadonly" 
        />
        <x-select 
            wire:model.live="classroom_id" 
            label="Kelas" 
            placeholder="Pilih Kelas"
            :options="$classrooms"
            :disabled="$isReadonly"
        />
        <x-select 
            wire:model.live="activity_id" 
            label="Ekstrakurikuler" 
            :placeholder="$classroom_id ? 'Pilih Ekstrakurikuler' : 'Pilih Kelas Terlebih Dahulu'"
            :options="$activities"
            :disabled="$isReadonly"
        />
    </div>

    @if($selectedActivity)
        <div class="p-4 rounded-xl bg-primary/5 border border-primary/20 flex items-start gap-4">
            <div class="p-3 bg-primary/10 rounded-xl">
                <x-icon name="o-trophy" class="size-8 text-primary" />
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-black tracking-tight">{{ $selectedActivity->name }}</h3>
                @if($selectedActivity->instructor)
                    <p class="text-xs opacity-60 font-medium whitespace-nowrap">Pembina: {{ $selectedActivity->instructor }}</p>
                @endif
                @if($selectedActivity->description)
                    <p class="text-xs opacity-60 mt-1">{{ $selectedActivity->description }}</p>
                @endif
            </div>
        </div>
    @endif

    <div class="flex flex-wrap gap-4">
        <x-badge label="Sangat Baik" class="badge-success badge-outline badge-sm" />
        <x-badge label="Baik" class="badge-info badge-outline badge-sm" />
        <x-badge label="Cukup" class="badge-warning badge-outline badge-sm" />
        <x-badge label="Perlu Ditingkatkan" class="badge-error badge-outline badge-sm" />
    </div>

    @if($classroom_id && $activity_id)
        <x-card shadow>
            <x-table :headers="[
                ['key' => 'name', 'label' => 'Nama Siswa'],
                ['key' => 'level', 'label' => 'Capaian', 'class' => 'w-48 text-center'],
                ['key' => 'description', 'label' => 'Keterangan (Opsional)']
            ]" :rows="$students">
                @scope('cell_name', $student)
                    <span class="font-bold">{{ $student->name }}</span>
                @endscope

                @scope('cell_level', $student)
                    <x-select 
                        wire:model="assessments_data.{{ $student->id }}.level" 
                        :options="[
                            ['id' => 'Sangat Baik', 'name' => 'Sangat Baik'],
                            ['id' => 'Baik', 'name' => 'Baik'],
                            ['id' => 'Cukup', 'name' => 'Cukup'],
                            ['id' => 'Perlu Ditingkatkan', 'name' => 'Perlu Ditingkatkan'],
                        ]"
                        :disabled="$isReadonly"
                        sm
                    />
                @endscope

                @scope('cell_description', $student)
                    <x-input 
                        wire:model="assessments_data.{{ $student->id }}.description" 
                        placeholder="Keterangan tambahan..."
                        :readonly="$isReadonly"
                        sm
                    />
                @endscope
            </x-table>

            @if(!$isReadonly)
                <x-slot:actions>
                    <x-button label="Simpan Penilaian" icon="o-check" class="btn-primary shadow-lg" wire:click="save" spinner="save" />
                </x-slot:actions>
            @endif
        </x-card>
    @else
        <div class="flex flex-col items-center justify-center py-20 text-base-content/30 border-2 border-dashed rounded-xl bg-base-200/50">
            <x-icon name="o-trophy" class="size-16 mb-2 opacity-20" />
            <p>Silakan pilih kelas dan ekstrakurikuler untuk memulai penilaian.</p>
        </div>
    @endif
</div>
