<x-modal id="import-modal" class="backdrop-blur">
    <x-header title="Import Data Siswa" subtitle="Upload file Excel untuk menambah data siswa secara massal." separator />

    <form wire:submit="import" class="space-y-6">
        <div class="p-4 bg-base-200 rounded-lg border border-dashed border-base-300">
            <div class="flex flex-col items-center gap-2 text-center">
                <x-icon name="o-document-text" class="size-8 opacity-40" />
                <div>
                    <div class="font-bold text-sm">Unduh Template</div>
                    <div class="text-xs opacity-60">Gunakan format Excel yang sudah kami sediakan.</div>
                </div>
                <x-button label="Download Template" icon="o-arrow-down-tray" wire:click="downloadTemplate" ghost sm />
            </div>
        </div>

        <x-file wire:model="importFile" label="File Excel/CSV" hint="Max 10MB" />

        @if (!empty($importErrors))
            <x-alert title="Gagal Validasi" icon="o-exclamation-triangle" class="alert-error mt-4">
                <div class="max-h-60 overflow-y-auto pr-2 custom-scrollbar">
                    <ul class="list-disc list-inside text-xs space-y-1">
                        @foreach ($importErrors as $error)
                            <li>
                                <strong>Baris {{ $error['row'] }}:</strong> {{ implode(', ', $error['errors']) }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </x-alert>
        @endif

        <x-slot:actions>
            <x-button label="Batal" wire:click="clearImport" @click="$dispatch('close-modal', 'import-modal')" />
            <x-button label="Proses Import" type="submit" class="btn-primary" spinner="import" />
        </x-slot:actions>
    </form>
</x-modal>
