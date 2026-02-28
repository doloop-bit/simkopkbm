@props([
    'label' => null,
])

<div x-data="{ open: false }" {{ $attributes->class(['relative inline-block']) }}>
    {{-- Trigger --}}
    <div @click="open = !open" @click.outside="open = false" class="cursor-pointer">
        @if(isset($trigger))
            {{ $trigger }}
        @elseif($label)
            <x-ui.button :label="$label" icon="o-chevron-down" />
        @else
            {{ $slot }}
        @endif
    </div>

    {{-- Content --}}
    <div
        x-show="open"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        x-cloak
        @click="open = false"
        class="absolute right-0 z-50 mt-2 w-48 origin-top-right rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-lg ring-1 ring-black/5 py-1"
    >
        @if(isset($content))
            {{ $content }}
        @endif
    </div>
</div>
