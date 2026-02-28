@props([
    'title' => null,
    'description' => null,
    'icon' => null,
    'dismissible' => false,
])

<div {{ $attributes->class(['rounded-2xl border p-4 flex items-start gap-3']) }} role="alert">
    @if($icon)
        <div class="shrink-0 mt-0.5">
            <x-ui.icon :name="$icon" class="w-5 h-5" />
        </div>
    @endif
    <div class="flex-1 min-w-0">
        @if($title)
            <p class="text-sm font-semibold">{{ $title }}</p>
        @endif
        @if($description)
            <p class="text-sm mt-0.5 opacity-80">{{ $description }}</p>
        @endif
        {{ $slot }}
    </div>
    @if($dismissible)
        <button type="button" class="shrink-0 p-1 rounded-lg hover:bg-black/5 transition-colors" @click="$el.closest('[role=alert]').remove()">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    @endif
</div>
