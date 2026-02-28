@props([
    'title' => null,
    'icon' => null,
    'link' => null,
    'badge' => null,
    'active' => false,
])

@php
    $isActive = $active || ($link && request()->url() === $link);
    $tag = $link ? 'a' : 'button';
@endphp

<{{ $tag }}
    @if($link) href="{{ $link }}" wire:navigate @endif
    @if($tag === 'button') type="button" @endif
    {{ $attributes->class([
        'flex items-center gap-3 w-full px-3 py-2.5 rounded-xl text-sm transition-all duration-200',
        'bg-emerald-600 text-white font-bold shadow-md' => $isActive,
        'text-slate-400 hover:text-white hover:bg-slate-800' => !$isActive,
    ]) }}
>
    @if($icon)
        <x-ui.icon :name="$icon" class="w-5 h-5 shrink-0" />
    @endif
    @if($title)
        <span class="truncate">{{ $title }}</span>
    @endif
    {{ $slot }}
    @if($badge)
        <span class="ml-auto inline-flex items-center rounded-full bg-primary/20 px-2 py-0.5 text-xs font-medium text-primary">
            {{ $badge }}
        </span>
    @endif
</{{ $tag }}>
