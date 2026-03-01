@props([
    'label' => null,
    'icon' => null,
    'type' => 'text',
    'sm' => false,
])

@php
    $wireModel = $attributes->wire('model')->value();
    $name = $attributes->get('name') ?? $wireModel;
    $inputClasses = 'w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all duration-200';
    $sizeClasses = $sm ? 'px-2.5 py-1.5 text-xs' : 'px-3 py-2 text-sm';
    $iconPadding = $icon ? 'pl-9' : '';
@endphp

<div>
    @if($label)
        <label @if($name) for="{{ $name }}" @endif class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
            {{ $label }}
        </label>
    @endif

    <div class="relative">
        @if($icon)
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400 dark:text-slate-500">
                <x-ui.icon :name="$icon" class="w-5 h-5" />
            </div>
        @endif

        <input
            type="{{ $type }}"
            @if($name) id="{{ $name }}" name="{{ $name }}" @endif
            {{ $attributes->except(['label', 'icon', 'sm'])->class([$inputClasses, $sizeClasses, $iconPadding]) }}
        />
    </div>

    @if($name)
        @error($name)
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
    @endif
</div>
