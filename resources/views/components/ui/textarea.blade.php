@props([
    'label' => null,
    'rows' => 3,
])

@php
    $wireModel = $attributes->wire('model')->value();
    $name = $attributes->get('name') ?? $wireModel;
    $textareaClasses = 'ui-textarea w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 transition-all duration-200 px-3 py-1.5 text-sm';
@endphp

<div>
    @if($label)
        <label @if($name) for="{{ $name }}" @endif class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
            {{ $label }}
        </label>
    @endif

    <textarea
        rows="{{ $rows }}"
        @if($name) id="{{ $name }}" name="{{ $name }}" @endif
        {{ $attributes->except(['label', 'rows'])->class([$textareaClasses]) }}
    >{{ $slot }}</textarea>

    @if($name)
        @error($name)
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
    @endif
</div>
