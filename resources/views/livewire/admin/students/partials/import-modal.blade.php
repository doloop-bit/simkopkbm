<flux:modal name="import-modal" class="max-w-md" x-on:close="$wire.clearImport()">
    <form wire:submit="import" class="space-y-6">
        <div>
            <flux:heading size="lg">Import Data Siswa</flux:heading>
            <flux:subheading>Upload file Excel untuk menambah data siswa secara massal.</flux:subheading>
        </div>

        <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg border border-dashed border-zinc-300 dark:border-zinc-700">
            <div class="flex flex-col items-center gap-2 text-center">
                <flux:icon icon="document-text" class="w-8 h-8 text-zinc-400" />
                <div>
                    <flux:heading size="sm">Unduh Template</flux:heading>
                    <flux:text size="xs">Gunakan format Excel yang sudah kami sediakan.</flux:text>
                </div>
                <flux:button variant="ghost" size="sm" icon="arrow-down-tray" wire:click="downloadTemplate">
                    Download Template
                </flux:button>
            </div>
        </div>

        <div class="space-y-2">
            <flux:label>File Excel/CSV</flux:label>
            <input type="file" wire:model="importFile" class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-1 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-zinc-800 dark:file:text-zinc-300" />
            @error('importFile') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
        </div>

        <div wire:loading wire:target="importFile" class="w-full mt-1 text-center">
            <div class="inline-flex items-center gap-3 text-sm text-blue-600 dark:text-blue-400">
                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="font-semibold">Mengunggah file...</span>
            </div>
        </div>

        @if (!empty($importErrors))
            <div class="space-y-2 mt-4 max-h-60 overflow-y-auto p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <flux:heading size="sm" class="text-red-800 dark:text-red-300">Gagal Validasi</flux:heading>
                <ul class="list-disc list-inside text-xs text-red-700 dark:text-red-400 space-y-1">
                    @foreach ($importErrors as $error)
                        <li>
                            <strong>Baris {{ $error['row'] }}:</strong> {{ implode(', ', $error['errors']) }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="ghost" wire:click="clearImport">Batal</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="import">
                <span wire:loading.remove wire:target="import">Proses Import</span>
                <span wire:loading wire:target="import">Memproses...</span>
            </flux:button>
        </div>
    </form>
</flux:modal>
