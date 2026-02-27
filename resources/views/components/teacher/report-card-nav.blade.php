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
    <div class="hidden lg:block sticky top-0 z-10 bg-base-100 border-b border-base-300 px-6 py-2">
        <div class="flex items-center gap-2 overflow-x-auto custom-scrollbar pb-1">
            @foreach($tabs as $key => $tab)
                <a 
                    href="{{ route($tab['route']) }}" 
                    wire:navigate 
                    class="flex items-center gap-2 px-4 py-2 rounded-lg transition-all whitespace-nowrap {{ request()->routeIs($tab['route']) ? 'bg-primary text-primary-content font-bold shadow-md' : 'hover:bg-base-200 opacity-70' }}"
                >
                    <x-icon name="o-{{ $tab['icon'] }}" class="size-4" />
                    <span class="text-sm">{{ $tab['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Mobile: Fixed bottom navigation with icons only --}}
    <div class="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-base-100 border-t border-base-300 safe-area-inset-bottom">
        <nav class="flex items-center justify-around px-2 py-2">
            @foreach($tabs as $key => $tab)
                <a href="{{ route($tab['route']) }}" wire:navigate
                    class="flex flex-col items-center justify-center gap-1 px-3 py-1 rounded-lg transition-colors {{ request()->routeIs($tab['route']) ? 'text-primary' : 'opacity-60' }}">
                    <x-icon name="o-{{ $tab['icon'] }}" class="size-6" />
                    <span class="text-[10px] uppercase font-bold tracking-tighter">{{ $tab['label_short'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    {{-- Safe area for devices with notch/home indicator --}}
    <style>
        .safe-area-inset-bottom {
            padding-bottom: env(safe-area-inset-bottom, 0);
        }
    </style>
@endif