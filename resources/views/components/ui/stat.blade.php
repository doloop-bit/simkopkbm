@props([
    'title' => null,
    'value' => null,
    'icon' => null,
    'description' => null,
    'color' => 'primary',
])

@php
    $colorClasses = match($color) {
        'success' => 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/30',
        'warning' => 'text-amber-600 bg-amber-50 dark:bg-amber-950/30',
        'error', 'danger' => 'text-red-600 bg-red-50 dark:bg-red-950/30',
        'info' => 'text-blue-600 bg-blue-50 dark:bg-blue-950/30',
        default => 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/30',
    };
@endphp

<div {{ $attributes->class(['rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-premium']) }}>
    <div class="flex items-start gap-4">
        @if($icon)
            <div class="{{ $colorClasses }} rounded-xl p-3">
                <x-ui.icon :name="$icon" class="w-6 h-6" />
            </div>
        @endif
        <div class="flex-1 min-w-0">
            @if($title)
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $title }}</p>
            @endif
            @if($value)
                <p class="text-2xl font-extrabold text-slate-900 dark:text-slate-100 tracking-tight mt-1">{{ $value }}</p>
            @endif
            @if($description)
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1.5">{{ $description }}</p>
            @endif
            {{ $slot }}
        </div>
    </div>
</div>
