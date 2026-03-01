@props(['tabs' => []])

@if (!empty($tabs))
    {{-- Desktop: Pill-style tab bar --}}
    <div class="hidden lg:flex items-center gap-1 bg-slate-100 dark:bg-slate-800/80 backdrop-blur-sm rounded-2xl p-1 border border-slate-200 dark:border-slate-700/60 shadow-sm overflow-x-auto custom-scrollbar">
        @foreach ($tabs as $key => $tab)
            @php
                $pattern = $tab['route_pattern'] ?? $tab['route'];
                $isActive = request()->routeIs($pattern);
            @endphp
            <a
                href="{{ route($tab['route']) }}"
                wire:navigate
                @class([
                    'flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium whitespace-nowrap transition-all',
                    'bg-white dark:bg-slate-900 text-primary font-bold shadow-sm border border-slate-200/80 dark:border-slate-700/50' => $isActive,
                    'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 hover:bg-white/70 dark:hover:bg-slate-900/50' => !$isActive,
                ])
            >
                <x-ui.icon name="{{ $tab['icon'] }}" class="size-4 shrink-0" />
                <span>{{ $tab['label'] }}</span>
            </a>
        @endforeach
    </div>

    {{-- Mobile: Fixed bottom navigation --}}
    <div class="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-200 dark:border-slate-700 safe-area-inset-bottom">
        <nav class="flex items-stretch justify-around">
            @foreach ($tabs as $key => $tab)
                @php
                    $pattern = $tab['route_pattern'] ?? $tab['route'];
                    $isActive = request()->routeIs($pattern);
                @endphp
                <a
                    href="{{ route($tab['route']) }}"
                    wire:navigate
                    @class([
                        'flex flex-col items-center justify-center gap-0.5 flex-1 py-2.5 transition-colors relative',
                        'text-primary' => $isActive,
                        'text-slate-400 dark:text-slate-500' => !$isActive,
                    ])
                >
                    @if ($isActive)
                        <span class="absolute top-0 inset-x-4 h-0.5 bg-primary rounded-b-full"></span>
                    @endif
                    <x-ui.icon name="{{ $tab['icon'] }}" class="size-5" />
                    <span class="text-[10px] font-bold tracking-tight">{{ $tab['label_short'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    @once
        <style>
            .safe-area-inset-bottom {
                padding-bottom: env(safe-area-inset-bottom, 0);
            }
        </style>
    @endonce
@endif
