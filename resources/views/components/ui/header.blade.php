@props([
    'title' => null,
    'subtitle' => null,
    'separator' => false,
])

<div {{ $attributes->class(['mb-6']) }}>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            @if($title)
                <h2 class="text-2xl font-extrabold text-slate-900 dark:text-slate-100 tracking-tight">{{ $title }}</h2>
            @endif
            @if($subtitle)
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ $subtitle }}</p>
            @endif
        </div>

        @if(isset($actions))
            <div class="flex items-center gap-2 shrink-0">
                {{ $actions }}
            </div>
        @endif
    </div>

    @if($separator)
        <div class="mt-4 border-b border-slate-200 dark:border-slate-700"></div>
    @endif
</div>
