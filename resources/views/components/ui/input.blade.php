@props([
    'label' => null,
    'icon' => null,
    'type' => 'text',
    'sm' => false,
    'variant' => 'default', // default, subtle
])

@php
    $wireModel = $attributes->wire('model')->value();
    $name = $attributes->get('name') ?? $wireModel;
    
    $baseClasses = 'ui-input w-full rounded-xl border transition-all duration-200 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none';
    
    $variantClasses = match($variant) {
        'subtle' => 'bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-sm focus:border-primary/50 focus:ring-primary/10',
        default => 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 focus:border-primary focus:ring-primary/20',
    };

    $textClasses = 'text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500';
    $sizeClasses = $sm ? 'px-2.5 py-1 h-8 text-xs' : 'px-3 py-1.5 text-sm';
    $iconPadding = $icon ? 'pl-10' : '';
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
            {{ $attributes->except(['label', 'icon', 'sm', 'variant'])->class([$baseClasses, $variantClasses, $textClasses, $sizeClasses, $iconPadding]) }}
        />
    </div>

    @if($name)
        @error($name)
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
    @endif
</div>
