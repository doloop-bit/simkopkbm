@props([
    'title' => null,
    'subtitle' => null,
    'separator' => false,
    'shadow' => false,
    'sm' => false,
    'padding' => null,
    'noSeparator' => false,
])

@php
    $paddingClass = $padding ?? ($sm ? 'p-4' : 'p-6');
    $shadowClass = $shadow ? 'shadow-premium' : '';
@endphp

<div {{ $attributes->class([
    'rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 transition-all duration-300',
    $paddingClass,
    $shadowClass,
]) }}>
    @if($title || isset($header))
        <div @class(['mb-4', 'pb-4 border-b border-slate-200 dark:border-slate-700' => $separator])>
            @if($title)
                <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100">{{ $title }}</h3>
            @endif
            @if($subtitle)
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">{{ $subtitle }}</p>
            @endif
            @if(isset($header))
                {{ $header }}
            @endif
        </div>
    @endif

    {{ $slot }}

    @if(isset($actions))
        <div @class(['flex items-center justify-end gap-3', 'mt-4 pt-4 border-t border-slate-200 dark:border-slate-700' => !$noSeparator])>
            {{ $actions }}
        </div>
    @endif
</div>
