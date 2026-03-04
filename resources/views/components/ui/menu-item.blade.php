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
    x-data="{ isItemHovered: false, tooltipTop: 0 }"
    @mouseenter="if(sidebarCollapsed) { isItemHovered = true; tooltipTop = $el.getBoundingClientRect().top + ($el.offsetHeight / 2); }"
    @mouseleave="isItemHovered = false"
    @focus="if(sidebarCollapsed) { isItemHovered = true; tooltipTop = $el.getBoundingClientRect().top + ($el.offsetHeight / 2); }"
    @blur="isItemHovered = false"
    @if($link) href="{{ $link }}" wire:navigate @endif
    @if($tag === 'button') type="button" @endif
    {{ $attributes->class([
        'relative flex w-full px-3 py-2.5 rounded-xl text-sm transition-all duration-200',
        'bg-emerald-600 text-white font-bold shadow-md' => $isActive,
        'text-slate-400 hover:text-white hover:bg-slate-800' => !$isActive,
    ]) }}
    :class="sidebarCollapsed && !inFlyout ? 'justify-center items-center' : 'items-center gap-3'"
>
    @if($icon)
        <x-ui.icon :name="$icon" class="w-5 h-5 shrink-0" />
    @endif
    @if($title)
        <span x-show="!sidebarCollapsed || inFlyout" class="truncate">{{ $title }}</span>
    @endif
    {{ $slot }}
    @if($badge)
        <span x-show="!sidebarCollapsed || inFlyout" class="ml-auto inline-flex items-center rounded-full bg-primary/20 px-2 py-0.5 text-xs font-medium text-primary">
            {{ $badge }}
        </span>
    @endif

    {{-- Standalone Tooltip Flyout --}}
    <template x-if="sidebarCollapsed && !inFlyout && isItemHovered">
        <div x-cloak 
             class="fixed left-20 ml-2 w-max px-3 py-1.5 rounded-lg border border-slate-700 bg-slate-800 text-sm font-semibold text-white shadow-xl z-[999] pointer-events-none"
             :style="'top: ' + tooltipTop + 'px; transform: translateY(-50%);'">
            {{ $title }}
        </div>
    </template>
</{{ $tag }}>
