@props([
    'title' => null,
    'persistent' => false,
    'maxWidth' => null,
])

@php
    $wireModel = $attributes->wire('model')->value();
    $maxWidthClass = $maxWidth ?? 'max-w-lg';
@endphp

<div
    x-data="{ show: @entangle($attributes->wire('model')) }"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    aria-modal="true"
    role="dialog"
>
    {{-- Backdrop --}}
    <div
        x-show="show"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm"
        @unless($persistent) @click="show = false" @endunless
    ></div>

    {{-- Dialog Panel --}}
    <div class="flex min-h-full items-center justify-center p-4">
        <div
            x-show="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            {{ $attributes->except(['wire:model', 'title', 'persistent', 'maxWidth'])->class([
                'relative w-full rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl p-6 custom-scrollbar max-h-[90vh] overflow-y-auto',
                $maxWidthClass,
            ]) }}
            @unless($persistent) @keydown.escape.window="show = false" @endunless
        >
            {{-- Close Button --}}
            <button 
                @click="show = false" 
                class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors bg-white/50 dark:bg-slate-900/50 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full p-1"
                aria-label="Close"
                type="button"
            >
                <x-ui.icon name="o-x-mark" class="w-5 h-5" />
            </button>

            @if($title)
                <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-4 pr-8">{{ $title }}</h3>
            @endif

            {{ $slot }}

            @if(isset($actions))
                <div class="flex items-center justify-end gap-3 mt-6 pt-4 border-t border-slate-200 dark:border-slate-700">
                    {{ $actions }}
                </div>
            @endif
        </div>
    </div>
</div>
