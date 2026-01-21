<flux:modal name="periodic-modal" class="max-w-md"
    x-on:periodic-saved.window="$flux.modal('periodic-modal').close()">
    <form wire:submit.prevent="savePeriodic({{ $editing?->latestProfile?->profileable_id ?? 0 }})"
        class="space-y-6">
        <div>
            <flux:heading size="lg">Data Periodik Siswa</flux:heading>
            <flux:subheading>Input data berat badan, tinggi, dan lingkar kepala.</flux:subheading>

            @if($hasExistingPeriodicData)
                <div
                    class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                    <div class="flex items-center gap-2">
                        <flux:icon icon="information-circle" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                        <div>
                            <span class="text-sm font-medium text-blue-800 dark:text-blue-200">Data sudah ada</span>
                            <span class="text-xs text-blue-600 dark:text-blue-400 block">Terakhir diupdate
                                {{ $periodicDataLastUpdated }}</span>
                        </div>
                    </div>
                </div>
            @else
                <div
                    class="mt-3 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                    <div class="flex items-center gap-2">
                        <flux:icon icon="exclamation-triangle" class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                        <span class="text-sm font-medium text-amber-800 dark:text-amber-200">Belum ada data untuk
                            semester ini</span>
                    </div>
                </div>
            @endif
        </div>

        <div class="space-y-4">
            <flux:select wire:model.live="semester" label="Semester">
                <option value="1">Ganjil (1)</option>
                <option value="2">Genap (2)</option>
            </flux:select>

            <flux:input type="number" step="0.5" wire:model="weight" label="Berat Badan (kg)" suffix="kg" />
            <flux:input type="number" step="1" wire:model="height" label="Tinggi Badan (cm)" suffix="cm" />
            <flux:input type="number" step="0.1" wire:model="head_circumference" label="Lingkar Kepala (cm)"
                suffix="cm" />
        </div>

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="ghost">Batal</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary">Simpan Data</flux:button>
        </div>
    </form>
</flux:modal>
