@props([
    'label' => null,
    'accept' => null,
    'hint' => null,
])

@php
    $wireModel = $attributes->wire('model')->value();
    $name = $attributes->get('name') ?? $wireModel;
@endphp

<div>
    @if($label)
        <label @if($name) for="{{ $name }}" @endif class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
            {{ $label }}
        </label>
    @endif

    <input
        type="file"
        @if($name) id="{{ $name }}" name="{{ $name }}" @endif
        @if($accept) accept="{{ $accept }}" @endif
        {{ $attributes->except(['label', 'accept', 'hint'])->class(['block w-full text-sm text-slate-500 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-primary/10 file:text-primary hover:file:bg-primary/20 file:cursor-pointer file:transition-colors']) }}
    />

    @if($hint)
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $hint }}</p>
    @endif

    @if($name)
        @error($name)
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
    @endif
</div>
