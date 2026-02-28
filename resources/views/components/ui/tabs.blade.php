@props([
    'selected' => null,
])

<div x-data="{ activeTab: @entangle($attributes->wire('model')).live ?? '{{ $selected }}' }" {{ $attributes->except(['wire:model', 'selected'])->class(['']) }}>
    {{-- Tab Headers --}}
    <div class="flex border-b border-slate-200 dark:border-slate-700 gap-1 overflow-x-auto">
        {{ $slot }}
    </div>
</div>
