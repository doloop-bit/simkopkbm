<x-ui.modal wire:model="importModal" persistent>
    <x-ui.header :title="__('Import Data Siswa')" :subtitle="__('Upload file Excel untuk menambah data siswa secara massal.')" separator />

    <form wire:submit="import" class="space-y-6">
        <div class="p-6 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-dashed border-slate-200 dark:border-slate-800">
            <div class="flex flex-col items-center gap-3 text-center">
                <x-ui.icon name="o-document-text" class="size-8 text-slate-300" />
                <div>
                    <div class="font-bold text-sm text-slate-900 dark:text-white">{{ __('Unduh Template') }}</div>
                    <div class="text-[10px] text-slate-400 font-medium">{{ __('Gunakan format Excel yang sudah kami sediakan.') }}</div>
                </div>
                <x-ui.button :label="__('Download Template')" icon="o-arrow-down-tray" wire:click="downloadTemplate" ghost sm />
            </div>
        </div>

        <x-ui.file wire:model="importFile" :label="__('File Excel/CSV')" :hint="__('Max 10MB')" required />

        @if (!empty($importErrors))
            <x-ui.alert :title="__('Gagal Validasi')" icon="o-exclamation-triangle" class="bg-rose-50 text-rose-800 border-rose-100 mt-4 shadow-sm" dismissible>
                <div class="max-h-60 overflow-y-auto pr-2 custom-scrollbar mt-2">
                    <ul class="list-disc list-inside text-[11px] space-y-2">
                        @foreach ($importErrors as $error)
                            <li class="leading-relaxed">
                                <span class="font-black border-b border-rose-200 pb-0.5 uppercase tracking-tighter">{{ __('Baris') }} {{ $error['row'] }}:</span> 
                                <span class="ml-1 opacity-90">{{ implode(', ', $error['errors']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </x-ui.alert>
        @endif

        <div class="flex justify-end gap-2 pt-4 border-t border-slate-100 dark:border-slate-800">
            <x-ui.button :label="__('Batal')" ghost wire:click="clearImport" @click="show = false" />
            <x-ui.button :label="__('Proses Import')" type="submit" class="btn-primary" spinner="import" />
        </div>
    </form>
</x-ui.modal>
