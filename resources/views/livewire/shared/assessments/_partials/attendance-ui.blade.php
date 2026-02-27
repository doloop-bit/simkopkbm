<div class="p-6 flex flex-col gap-6">
    <x-header title="Presensi Rapor" subtitle="Input rekapitulasi ketidakhadiran siswa untuk ditampilkan di rapor." separator />

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-select wire:model.live="academic_year_id" label="Tahun Ajaran" :options="$years" />
        <x-select 
            wire:model.live="semester" 
            label="Semester" 
            :options="[
                ['id' => '1', 'name' => '1 (Ganjil)'],
                ['id' => '2', 'name' => '2 (Genap)'],
            ]" 
        />
        <x-select 
            wire:model.live="classroom_id" 
            label="Kelas" 
            placeholder="Pilih Kelas"
            :options="$classrooms"
        />
    </div>

    @if($classroom_id)
        <x-card shadow>
            <x-table :headers="[
                ['key' => 'name', 'label' => 'Nama Siswa'],
                ['key' => 'sick', 'label' => 'Sakit', 'class' => 'text-center w-32'],
                ['key' => 'permission', 'label' => 'Izin', 'class' => 'text-center w-32'],
                ['key' => 'absent', 'label' => 'Alpha', 'class' => 'text-center w-32']
            ]" :rows="$students">
                @scope('cell_name', $student)
                    <span class="font-bold">{{ $student->name }}</span>
                @endscope

                @scope('cell_sick', $student)
                    <x-input 
                        wire:model="attendance_data.{{ $student->id }}.sick" 
                        type="number" 
                        min="0"
                        class="text-center font-bold"
                        sm
                    />
                @endscope

                @scope('cell_permission', $student)
                    <x-input 
                        wire:model="attendance_data.{{ $student->id }}.permission" 
                        type="number" 
                        min="0"
                        class="text-center font-bold"
                        sm
                    />
                @endscope

                @scope('cell_absent', $student)
                    <x-input 
                        wire:model="attendance_data.{{ $student->id }}.absent" 
                        type="number" 
                        min="0"
                        class="text-center font-bold"
                        sm
                    />
                @endscope
            </x-table>

            <x-slot:actions>
                <x-button label="Simpan Presensi" icon="o-check" class="btn-primary shadow-lg" wire:click="save" spinner="save" />
            </x-slot:actions>
        </x-card>
    @else
        <div class="flex flex-col items-center justify-center py-20 text-base-content/30 border-2 border-dashed rounded-xl bg-base-200/50">
            <x-icon name="o-calendar-days" class="size-16 mb-2 opacity-20" />
            <p>Silakan pilih kelas untuk memulai penginputan presensi.</p>
        </div>
    @endif
</div>
