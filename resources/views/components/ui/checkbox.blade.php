@props([
    'label' => null,
])

@php
    $wireModel = $attributes->wire('model')->value();
    $name = $attributes->get('name') ?? $wireModel;
@endphp

<label class="inline-flex items-center gap-2 cursor-pointer select-none">
    <input
        type="checkbox"
        @if($name) id="{{ $name }}" name="{{ $name }}" @endif
        {{ $attributes->except(['label'])->class(['h-4 w-4 rounded border-slate-300 dark:border-slate-600 text-primary focus:ring-primary/30 dark:bg-slate-800 transition-colors']) }}
    />
    @if($label)
        <span class="text-sm text-slate-700 dark:text-slate-300">{{ $label }}</span>
    @endif
</label>
