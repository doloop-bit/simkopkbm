@props([
    'title' => null,
    'icon' => null,
])

@php
    // Check if any child route is active
    $isOpen = false;
    // The sub-menu starts open if the current URL matches any child link
@endphp

<div x-data="{ open: {{ $isOpen ? 'true' : 'false' }} }" class="space-y-0.5">
    <button
        type="button"
        @click="open = !open"
        {{ $attributes->class([
            'flex items-center justify-between w-full px-3 py-2.5 rounded-xl text-sm text-slate-400 hover:text-white hover:bg-slate-800 transition-all duration-200',
        ]) }}
    >
        <span class="flex items-center gap-3">
            @if($icon)
                <x-ui.icon :name="$icon" class="w-5 h-5 shrink-0" />
            @endif
            @if($title)
                <span class="truncate">{{ $title }}</span>
            @endif
        </span>
        <svg :class="{ 'rotate-180': open }" class="w-4 h-4 shrink-0 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    <div
        x-show="open"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        x-cloak
        class="ml-4 pl-4 border-l border-slate-700/50 space-y-0.5"
    >
        {{ $slot }}
    </div>
</div>
