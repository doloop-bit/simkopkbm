@if(
        request()->routeIs('admin.report-card.*') ||
        request()->routeIs('admin.assessments.attendance') ||
        request()->routeIs('admin.assessments.extracurricular')
    )

    @props(['current' => null])

    @php
        $tabs = [
            'grading' => [
                'label' => 'Input Nilai & TP',
                'label_short' => 'Nilai',
                'icon' => 'o-clipboard-document-list',
                'route' => 'admin.report-card.grading',
            ],
            'attendance' => [
                'label' => 'Input Kehadiran',
                'label_short' => 'Hadir',
                'icon' => 'o-calendar-days',
                'route' => 'admin.assessments.attendance',
            ],
            'extracurricular' => [
                'label' => 'Input Ekskul',
                'label_short' => 'Ekskul',
                'icon' => 'o-trophy',
                'route' => 'admin.assessments.extracurricular',
            ],
            'create' => [
                'label' => 'Buat Rapor',
                'label_short' => 'Rapor',
                'icon' => 'o-document-text',
                'route' => 'admin.report-card.create',
            ],
        ];
        
        $activeTab = collect($tabs)->first(fn($tab) => request()->routeIs($tab['route']))['route'] ?? '';
    @endphp

    {{-- Desktop: Horizontal navbar below header --}}
    <div class="hidden lg:block mb-6">
        <x-tabs wire:model="activeTab" class="bg-base-100 p-0">
            @foreach($tabs as $key => $tab)
                <x-tab 
                    name="{{ route($tab['route']) }}" 
                    label="{{ $tab['label'] }}" 
                    icon="{{ $tab['icon'] }}" 
                    :link="route($tab['route'])"
                />
            @endforeach
        </x-tabs>
    </div>

    {{-- Mobile: Fixed bottom navigation with icons only --}}
    <div
        class="lg:hidden fixed bottom-16 left-0 right-0 z-40 bg-base-100 border-t border-base-200 safe-area-inset-bottom">
        <nav class="flex items-center justify-around px-2 py-2">
            @foreach($tabs as $key => $tab)
                <a href="{{ route($tab['route']) }}" wire:navigate
                    class="flex flex-col items-center justify-center gap-1 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs($tab['route']) ? 'text-primary bg-primary/10' : 'text-base-content/60 hover:text-base-content' }}">
                    <x-icon :name="$tab['icon']" class="size-6" />
                    <span class="text-xs font-medium">{{ $tab['label_short'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    {{-- Add bottom padding to main content on mobile to prevent content being hidden behind bottom nav --}}
    <style>
        @media (max-width: 1023px) {
            .mary-content {
                padding-bottom: 8rem !important;
            }
        }

        /* Safe area for devices with notch/home indicator */
        .safe-area-inset-bottom {
            padding-bottom: env(safe-area-inset-bottom);
        }
    </style>
@endif