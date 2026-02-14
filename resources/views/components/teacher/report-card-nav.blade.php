@if(
        request()->routeIs('teacher.report-cards') ||
        request()->routeIs('teacher.assessments.grading') ||
        request()->routeIs('teacher.assessments.attendance') ||
        request()->routeIs('teacher.assessments.extracurricular')
    )

    @props(['current' => null])

    @php
        $tabs = [
            'grades' => [
                'label' => 'Input Nilai & TP',
                'label_short' => 'Nilai',
                'icon' => 'clipboard-document-list',
                'route' => 'teacher.assessments.grading',
            ],
            'attendance' => [
                'label' => 'Input Kehadiran',
                'label_short' => 'Hadir',
                'icon' => 'calendar-days',
                'route' => 'teacher.assessments.attendance',
            ],
            'extracurricular' => [
                'label' => 'Input Ekskul',
                'label_short' => 'Ekskul',
                'icon' => 'trophy',
                'route' => 'teacher.assessments.extracurricular',
            ],
            'report_cards' => [
                'label' => 'Buat Rapor',
                'label_short' => 'Rapor',
                'icon' => 'document-text',
                'route' => 'teacher.report-cards',
            ],
            'home' => [
                'label' => 'Kembali',
                'label_short' => 'Home',
                'icon' => 'home',
                'route' => 'teacher.dashboard',
            ],
        ];
    @endphp

    {{-- Desktop: Horizontal navbar below header --}}
    <flux:header class="hidden lg:block bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700 lg:px-6">
        <flux:navbar>
            @foreach($tabs as $key => $tab)
                <flux:navbar.item :icon="$tab['icon']" :href="route($tab['route'])" :current="request()->routeIs($tab['route'])"
                    wire:navigate.hover>
                    {{ $tab['label'] }}
                </flux:navbar.item>
            @endforeach
        </flux:navbar>
    </flux:header>

    {{-- Mobile: Fixed bottom navigation with icons only --}}
    <div
        class="lg:hidden fixed bottom-0 left-0 right-0 z-50 bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-700 safe-area-inset-bottom">
        <nav class="flex items-center justify-around px-2 py-2">
            @foreach($tabs as $key => $tab)
                <a href="{{ route($tab['route']) }}" wire:navigate
                    class="flex flex-col items-center justify-center gap-1 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs($tab['route']) ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' }}">
                    <flux:icon :icon="$tab['icon']" class="size-6" />
                    <span class="text-xs font-medium">{{ $tab['label_short'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    {{-- Add bottom padding to main content on mobile to prevent content being hidden behind bottom nav --}}
    <style>
        @media (max-width: 1023px) {

            flux-main,
            [data-flux-main] {
                padding-bottom: 5rem !important;
            }
        }

        /* Safe area for devices with notch/home indicator */
        .safe-area-inset-bottom {
            padding-bottom: env(safe-area-inset-bottom);
        }
    </style>
@endif