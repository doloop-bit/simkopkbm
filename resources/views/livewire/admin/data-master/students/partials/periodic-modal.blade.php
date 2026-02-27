<x-modal wire:model="periodicModal" class="backdrop-blur">
    <x-header title="Data Periodik Siswa" subtitle="Input data berat badan, tinggi, dan lingkar kepala." separator />

    <form wire:submit.prevent="savePeriodic({{ $editing?->latestProfile?->profileable_id ?? 0 }})" class="space-y-6">
        @if($hasExistingPeriodicData)
            <x-alert title="Data sudah ada" icon="o-information-circle" class="alert-info">
                Terakhir diupdate {{ $periodicDataLastUpdated }}
            </x-alert>
        @else
            <x-alert title="Belum ada data" icon="o-exclamation-triangle" class="alert-warning text-xs">
                Belum ada data untuk semester ini
            </x-alert>
        @endif

        <div class="space-y-4">
            <x-select 
                wire:model.live="semester" 
                label="Semester" 
                :options="[
                    ['id' => 1, 'name' => 'Ganjil (1)'],
                    ['id' => 2, 'name' => 'Genap (2)'],
                ]"
            />

            <x-input type="number" step="0.5" wire:model="weight" label="Berat Badan (kg)" suffix="kg" />
            <x-input type="number" step="1" wire:model="height" label="Tinggi Badan (cm)" suffix="cm" />
            <x-input type="number" step="0.1" wire:model="head_circumference" label="Lingkar Kepala (cm)" suffix="cm" />
        </div>

        <x-slot:actions>
            <x-button label="Batal" @click="$set('periodicModal', false)" />
            <x-button label="Simpan Data" type="submit" class="btn-primary" spinner="savePeriodic" />
        </x-slot:actions>
    </form>
</x-modal>
