<div class="flex items-center justify-between gap-2 py-1.5 px-2 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800/50" wire:key="material-{{ $material->id }}">
    <div class="flex items-center gap-2 min-w-0">
        @php
            $icon = match ($material->type) {
                'video' => 'o-play-circle',
                'slides' => 'o-document-text',
                'handbook' => 'o-book-open',
                default => 'o-document',
            };
        @endphp
        <x-ui.icon :name="$icon" class="w-4 h-4 text-slate-500 shrink-0" />
        <span class="text-sm truncate">{{ $material->title }}</span>
        @unless($material->is_published)
            <x-ui.badge :label="__('Draf')" variant="amber" flat size="xs" />
        @endunless
    </div>
    <div class="flex items-center gap-0.5 shrink-0">
        <x-ui.button icon="o-arrow-up" ghost size="xs" wire:click="moveMaterial({{ $material->id }}, 'up')" :disabled="$first" />
        <x-ui.button icon="o-arrow-down" ghost size="xs" wire:click="moveMaterial({{ $material->id }}, 'down')" :disabled="$last" />
        <x-ui.button :icon="$material->is_published ? 'o-eye' : 'o-eye-slash'" ghost size="xs" wire:click="toggleMaterial({{ $material->id }})" :title="__('Terbit / Draf')" />
        <x-ui.button icon="o-pencil-square" ghost size="xs" wire:click="editMaterial({{ $material->id }})" />
        <x-ui.button icon="o-trash" ghost size="xs" class="text-red-600" wire:confirm="{{ __('Hapus materi ini?') }}" wire:click="deleteMaterial({{ $material->id }})" />
    </div>
</div>
