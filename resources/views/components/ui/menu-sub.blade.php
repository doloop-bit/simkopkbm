@props([
    'title' => null,
    'icon' => null,
    'active' => false,
    'open' => false,
])

@php
    $isOpen = $open || $active;
@endphp

<div x-data="{ open: {{ $isOpen ? 'true' : 'false' }}, isSubHovered: false, flyoutTop: 0 }" 
     class="space-y-0.5"
     @mouseenter="if(sidebarCollapsed) { isSubHovered = true; flyoutTop = $refs.triggerBtn.getBoundingClientRect().top; }"
     @mouseleave="isSubHovered = false"
>
    <button
        x-ref="triggerBtn"
        type="button"
        @click="if(!sidebarCollapsed) open = !open"
        {{ $attributes->class([
            'flex items-center w-full px-3 py-2.5 rounded-xl text-sm transition-all duration-200',
            'bg-slate-800/40 text-white font-medium shadow-sm border border-slate-700/50 mb-1' => $active,
            'text-slate-400 hover:text-white hover:bg-slate-800' => !$active,
        ]) }}
        :class="sidebarCollapsed && !inFlyout ? 'justify-center' : 'justify-between'"
    >
        <span class="flex items-center" :class="sidebarCollapsed && !inFlyout ? '' : 'gap-3'">
            @if($icon)
                <x-ui.icon :name="$icon" class="w-5 h-5 shrink-0" />
            @endif
            @if($title)
                <span x-show="!sidebarCollapsed || inFlyout" class="truncate">{{ $title }}</span>
            @endif
        </span>
        <svg x-show="!sidebarCollapsed || inFlyout" :class="{ 'rotate-180': open }" class="w-4 h-4 shrink-0 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    {{-- Inline Menu (Expanded Sidebar) --}}
    <div
        x-show="!sidebarCollapsed && open"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        x-cloak
        class="ml-4 pl-4 border-l border-slate-700/50 space-y-0.5 mt-2"
    >
        {{ $slot }}
    </div>

    {{-- Flyout Menu (Collapsed Sidebar) --}}
    <div
        x-data="{ inFlyout: true }"
        x-show="sidebarCollapsed && isSubHovered"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-x-2"
        x-transition:enter-end="opacity-100 translate-x-0"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-x-0"
        x-transition:leave-end="opacity-0 -translate-x-2"
        x-cloak
        class="fixed left-20 ml-2 w-56 rounded-xl border border-slate-700 bg-slate-800 shadow-xl py-2 z-[999] overflow-hidden"
        :style="'top: ' + flyoutTop + 'px;'"
    >
        <div class="px-4 py-2 border-b border-slate-700/50 mb-2">
            <span class="text-xs font-bold text-slate-300 uppercase tracking-wider">{{ $title }}</span>
        </div>
        
        <div class="px-2 pb-1 space-y-1">
            {{ $slot }}
        </div>
    </div>
</div>
