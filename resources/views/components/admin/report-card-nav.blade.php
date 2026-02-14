@props(['current' => null])

@php
    $tabs = [
        'grading' => [
            'label' => 'Input Nilai & TP',
            'label_short' => 'Nilai',
            'icon' => 'clipboard-document-list',
            'route' => 'admin.report-card.grading',
        ],
        'attendance' => [
            'label' => 'Input Kehadiran',
            'label_short' => 'Hadir',
            'icon' => 'calendar-days',
            'route' => 'admin.assessments.attendance',
        ],
        'create' => [
            'label' => 'Buat Rapor',
            'label_short' => 'Rapor',
            'icon' => 'document-text',
            'route' => 'admin.report-card.create',
        ],
    ];
@endphp

{{-- Desktop: Horizontal Navigation at Top --}}
<div class="hidden md:block bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700 mb-4">
    <div class="pt-0 pb-0">
        <flux:navbar class="-mb-px">
            @foreach($tabs as $key => $tab)
                <flux:navbar.item :icon="$tab['icon']" :href="route($tab['route'])"
                    :current="request()->routeIs($tab['route'])" wire:navigate.hover>
                    {{ $tab['label'] }}
                </flux:navbar.item>
            @endforeach
        </flux:navbar>
    </div>
</div>

{{-- Mobile: Bottom Navigation Bar (Fixed) --}}
<div
    class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-700 safe-area-inset-bottom">
    <nav class="flex items-center justify-around h-16">
        @foreach($tabs as $key => $tab)
            <a href="{{ route($tab['route']) }}" wire:navigate.hover @class([
                'flex flex-col items-center justify-center flex-1 h-full gap-1 transition-colors',
                'text-blue-600 dark:text-blue-400' => request()->routeIs($tab['route']),
                'text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' => !request()->routeIs($tab['route']),
            ])>
                <flux:icon :icon="$tab['icon']" @class([
                    'w-6 h-6',
                    'scale-110' => request()->routeIs($tab['route']),
                ]) />
                <span @class([
                    'text-xs font-medium',
                    'font-semibold' => request()->routeIs($tab['route']),
                ])>
                    {{ $tab['label_short'] }}
                </span>
            </a>
        @endforeach
    </nav>
</div>