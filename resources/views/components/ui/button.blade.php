@props([
    'label' => null,
    'icon' => null,
    'type' => 'button',
    'variant' => 'default', // default, primary, secondary, ghost, outline, danger
    'ghost' => false,
    'sm' => false,
    'spinner' => null,
    'link' => null,
])

@php
    $tag = $link ? 'a' : 'button';
    $baseClasses = 'inline-flex items-center justify-center gap-2 font-medium rounded-xl transition-all duration-200 cursor-pointer select-none disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-primary/30 active:scale-95';
    
    // Support legacy ghost prop
    $effectiveVariant = ($variant === 'default' && $ghost) ? 'ghost' : $variant;
    
    $variantClasses = match($effectiveVariant) {
        'primary' => 'btn-primary',
        'secondary' => 'bg-slate-800 hover:bg-slate-900 text-white shadow-lg shadow-slate-800/20',
        'ghost' => 'btn-ghost text-slate-600 dark:text-slate-300',
        'outline' => 'border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300',
        'soft' => 'bg-primary/10 text-primary hover:bg-primary/20 border-none',
        'danger' => 'bg-red-500 hover:bg-red-600 text-white shadow-lg shadow-red-500/20',
        default => '',
    };
    
    $sizeClasses = $sm ? 'px-3 py-1 text-xs' : 'px-4 py-1.5 text-sm';
    $spinnerTarget = $spinner === true ? null : $spinner;
@endphp

<{{ $tag }}
    @if($link) href="{{ $link }}" wire:navigate @endif
    @if($tag === 'button') type="{{ $type }}" @endif
    {{ $attributes->class([$baseClasses, $sizeClasses, $variantClasses]) }}
    @if($spinnerTarget)
        wire:loading.attr="disabled"
        wire:target="{{ $spinnerTarget }}"
    @elseif($spinner === true)
        wire:loading.attr="disabled"
    @endif
>
    @if($icon)
        <x-ui.icon :name="$icon" @class(['w-4 h-4' => $sm, 'w-5 h-5' => !$sm]) />
    @endif
    @if($spinner)
        <span
            @if($spinnerTarget)
                wire:loading wire:target="{{ $spinnerTarget }}"
            @else
                wire:loading
            @endif
            class="inline-block"
        >
            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
        </span>
    @endif
    @if($label)
        <span @if($spinner) wire:loading.remove @if($spinnerTarget) wire:target="{{ $spinnerTarget }}" @endif @endif>{{ $label }}</span>
    @endif
    {{ $slot }}
</{{ $tag }}>
