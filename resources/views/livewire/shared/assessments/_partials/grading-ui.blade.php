<div class="p-6 flex flex-col gap-6">
    <x-header title="Penilaian Rapor (Nilai & TP)" subtitle="Input nilai akhir dan pemilihan TP kompetensi untuk rapor." separator />

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <x-select wire:model.live="academic_year_id" label="Tahun Ajaran" :options="$years" :disabled="$isReadonly" />
        <x-select 
            wire:model.live="semester" 
            label="Semester" 
            :options="[
                ['id' => '1', 'name' => '1 (Ganjil)'],
                ['id' => '2', 'name' => '2 (Genap)'],
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
            wire:model.live="subject_id" 
            label="Mata Pelajaran" 
            placeholder="Pilih Mata Pelajaran"
            :options="$subjects"
            :disabled="$isReadonly"
        />
    </div>

    @if($currentPhase)
        <div class="flex items-center gap-2">
            <x-badge label="Fase {{ $currentPhase }}" class="badge-primary shadow-sm" />
            <span class="text-xs opacity-60 italic">TP yang ditampilkan sesuai dengan fase kelas yang dipilih.</span>
        </div>
    @endif

    @if($classroom_id && $subject_id)
        @if($tps->isEmpty())
            <div class="alert alert-warning shadow-sm border-warning/20">
                <x-icon name="o-exclamation-triangle" class="size-5" />
                <div class="text-sm">
                    Belum ada TP untuk mata pelajaran ini @if($currentPhase) pada Fase {{ $currentPhase }} @endif. 
                    Silakan tambahkan TP melalui menu <strong>Mata Pelajaran (Admin)</strong>.
                </div>
            </div>
        @endif

        <x-card shadow class="pb-20">
            <x-table :headers="[
                ['key' => 'name', 'label' => 'Nama Siswa'],
                ['key' => 'grade', 'label' => 'Nilai Final (0-100)', 'class' => 'text-center w-32'],
                ['key' => 'best_tp', 'label' => 'TP Tertinggi (Kompeten)'],
                ['key' => 'improvement_tp', 'label' => 'TP Terendah (Perlu Bimbingan)']
            ]" :rows="$students">
                @scope('cell_name', $student)
                    <span class="font-bold">{{ $student->name }}</span>
                @endscope

                @scope('cell_grade', $student)
                    <x-input 
                        wire:model="grades_data.{{ $student->id }}.grade" 
                        type="number" 
                        min="0"
                        max="100" 
                        step="1" 
                        class="text-center font-bold" 
                        :readonly="$isReadonly"
                        sm
                    />
                @endscope

                @scope('cell_best_tp', $student)
                    <x-choices
                        wire:model="grades_data.{{ $student->id }}.best_tp_ids"
                        :options="$tps"
                        option-label="code"
                        placeholder="Pilih TP Terbaik..."
                        :disabled="$isReadonly"
                        compact
                        sm
                    />
                @endscope

                @scope('cell_improvement_tp', $student)
                    <x-choices
                        wire:model="grades_data.{{ $student->id }}.improvement_tp_ids"
                        :options="$tps"
                        option-label="code"
                        placeholder="Pilih TP Lemah..."
                        :disabled="$isReadonly"
                        compact
                        sm
                    />
                @endscope
            </x-table>

            @if(!$isReadonly)
                <x-slot:actions>
                    <x-button label="Simpan Penilaian Rapor" icon="o-check" class="btn-primary shadow-lg" wire:click="save" spinner="save" />
                </x-slot:actions>
            @endif
        </x-card>
    @else
        <div class="flex flex-col items-center justify-center py-20 text-base-content/30 border-2 border-dashed rounded-xl bg-base-200/50">
            <x-icon name="o-pencil-square" class="size-16 mb-2 opacity-20" />
            <p>Silakan pilih kelas dan mata pelajaran untuk memulai penilaian rapor.</p>
        </div>
    @endif
</div>
