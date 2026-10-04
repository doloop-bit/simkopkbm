<div class="flex items-center justify-between gap-3 py-2 px-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/60 transition-colors border border-transparent hover:border-slate-200 dark:hover:border-slate-700/60" wire:key="material-{{ $material->id }}">
    <div class="flex items-center gap-2.5 min-w-0">
        @php
            $typeMeta = match ($material->type) {
                'video' => ['icon' => 'o-play-circle', 'badge' => 'Video', 'variant' => 'error'],
                'slides' => ['icon' => 'o-document-text', 'badge' => 'Slide PDF', 'variant' => 'info'],
                'handbook' => ['icon' => 'o-book-open', 'badge' => 'Buku Pegangan', 'variant' => 'warning'],
                default => ['icon' => 'o-document', 'badge' => 'Teks', 'variant' => 'neutral'],
            };
        @endphp
        <div class="p-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 shrink-0">
            <x-ui.icon :name="$typeMeta['icon']" class="w-4 h-4" />
        </div>
        <div class="min-w-0">
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-slate-800 dark:text-slate-200 truncate">{{ $material->title }}</span>
                <x-ui.badge :label="$typeMeta['badge']" :variant="$typeMeta['variant']" flat size="xs" />
                @unless($material->is_published)
                    <x-ui.badge :label="__('Draf')" variant="amber" flat size="xs" />
                @endunless
            </div>
            @if(!empty($material->attachments))
                <span class="text-[11px] text-slate-400 flex items-center gap-1 mt-0.5">
                    <x-ui.icon name="o-paper-clip" class="w-3 h-3" />
                    {{ count($material->attachments) }} file
                </span>
            @endif
        </div>
    </div>
    <div class="flex items-center gap-0.5 shrink-0">
        <x-ui.button icon="o-arrow-up" ghost size="xs" wire:click="moveMaterial({{ $material->id }}, 'up')" :disabled="$first" :title="__('Geser ke atas')" />
        <x-ui.button icon="o-arrow-down" ghost size="xs" wire:click="moveMaterial({{ $material->id }}, 'down')" :disabled="$last" :title="__('Geser ke bawah')" />
        <x-ui.button :icon="$material->is_published ? 'o-eye' : 'o-eye-slash'" ghost size="xs" wire:click="toggleMaterial({{ $material->id }})" :title="$material->is_published ? __('Sembunyikan (Jadikan Draf)') : __('Terbitkan')" />
        <x-ui.button icon="o-pencil-square" ghost size="xs" wire:click="editMaterial({{ $material->id }})" :title="__('Edit Materi')" />
        <x-ui.button icon="o-trash" ghost size="xs" class="text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-950/20" wire:confirm="{{ __('Hapus materi ini?') }}" wire:click="deleteMaterial({{ $material->id }})" :title="__('Hapus')" />
    </div>
</div>
