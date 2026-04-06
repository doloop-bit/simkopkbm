@props([
    'item' => null,
    'value' => null,
    'subValue' => null,
    'sub_value' => null, // Support for alternate name
    'avatar' => null,
    'icon' => null,
    'actions' => null,
])

@php
    $subValue = $subValue ?? $sub_value;
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center gap-3 py-1 bg-transparent']) }}>
    @if($avatar)
        <div class="shrink-0">
            {{ $avatar }}
        </div>
    @elseif($icon)
        <div class="shrink-0 p-2 rounded-lg bg-base-200 text-slate-500">
            <x-ui.icon :name="$icon" class="size-5" />
        </div>
    @endif

    <div class="flex-1 min-w-0">
        <div class="text-sm font-semibold text-slate-900 dark:text-white truncate">
            {{ $value ?? $slot }}
        </div>
        @if($subValue)
            <div class="text-xs text-slate-500 dark:text-slate-400 truncate">
                {{ $subValue }}
            </div>
        @endif
    </div>

    @if($actions)
        <div class="shrink-0 flex items-center gap-1">
            {{ $actions }}
        </div>
    @endif
</div>
