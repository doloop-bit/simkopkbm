<x-ui.modal wire:model="periodicModal" persistent>
    <x-ui.header :title="__('Data Periodik Siswa')" :subtitle="__('Input data berat badan, tinggi, dan lingkar kepala.')" separator />

    <form wire:submit.prevent="savePeriodic({{ $editing?->latestProfile?->profileable_id ?? 0 }})" class="space-y-6">
        @if($hasExistingPeriodicData)
            <x-ui.alert :title="__('Data sudah ada')" icon="o-information-circle" class="bg-blue-50 text-blue-800 border-blue-100 shadow-sm">
                {{ __('Terakhir diupdate') }} {{ $periodicDataLastUpdated }}
            </x-ui.alert>
        @else
            <x-ui.alert :title="__('Belum ada data')" icon="o-exclamation-triangle" class="bg-amber-50 text-amber-800 border-amber-100 shadow-sm">
                {{ __('Belum ada data untuk semester ini.') }}
            </x-ui.alert>
        @endif

        <div class="space-y-4">
            <x-ui.select 
                wire:model.live="semester" 
                :label="__('Semester')" 
                :options="[
                    ['id' => 1, 'name' => __('Ganjil (1)')],
                    ['id' => 2, 'name' => __('Genap (2)')],
                ]"
                required
            />

            <x-ui.input type="number" step="0.5" wire:model="weight" :label="__('Berat Badan (kg)')" suffix="kg" required />
            <x-ui.input type="number" step="1" wire:model="height" :label="__('Tinggi Badan (cm)')" suffix="cm" required />
            <x-ui.input type="number" step="0.1" wire:model="head_circumference" :label="__('Lingkar Kepala (cm)')" suffix="cm" required />
        </div>

        <div class="flex justify-end gap-2 pt-4 border-t border-slate-100 dark:border-slate-800">
            <x-ui.button :label="__('Batal')" ghost @click="$set('periodicModal', false)" />
            <x-ui.button :label="__('Simpan Data')" type="submit" class="btn-primary" spinner="savePeriodic" />
        </div>
    </form>
</x-ui.modal>
