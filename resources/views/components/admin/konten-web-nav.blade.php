@if(request()->routeIs('admin.school-profile.*') || 
    request()->routeIs('admin.news.*') || 
    request()->routeIs('admin.gallery.*') || 
    request()->routeIs('admin.programs.*') || 
    request()->routeIs('admin.contact-inquiries.*'))

@php
    $tabs = [
        'school-profile' => [
            'label' => 'Profil Sekolah',
            'label_short' => 'Profil',
            'icon' => 'o-building-office-2',
            'route' => 'admin.school-profile.edit',
            'route_pattern' => 'admin.school-profile.*',
        ],
        'news' => [
            'label' => 'Berita & Artikel',
            'label_short' => 'Berita',
            'icon' => 'o-newspaper',
            'route' => 'admin.news.index',
            'route_pattern' => 'admin.news.*',
        ],
        'gallery' => [
            'label' => 'Galeri',
            'label_short' => 'Galeri',
            'icon' => 'o-photo',
            'route' => 'admin.gallery.index',
            'route_pattern' => 'admin.gallery.*',
        ],
        'programs' => [
            'label' => 'Program Pendidikan',
            'label_short' => 'Program',
            'icon' => 'o-academic-cap',
            'route' => 'admin.programs.index',
            'route_pattern' => 'admin.programs.*',
        ],
        'contact' => [
            'label' => 'Pesan Kontak',
            'label_short' => 'Pesan',
            'icon' => 'o-envelope',
            'route' => 'admin.contact-inquiries.index',
            'route_pattern' => 'admin.contact-inquiries.*',
        ],
    ];
    
    $activeTab = collect($tabs)->first(fn($tab) => request()->routeIs($tab['route_pattern']))['route'] ?? '';
@endphp

    {{-- Desktop: Horizontal navbar below header --}}
    <div class="hidden lg:block sticky top-0 z-10 bg-base-100 border-b border-base-300 px-6 py-2 mb-6">
        <div class="flex items-center gap-2 overflow-x-auto custom-scrollbar pb-1">
            @foreach($tabs as $key => $tab)
                <a 
                    href="{{ route($tab['route']) }}" 
                    wire:navigate 
                    class="flex items-center gap-2 px-4 py-2 rounded-lg transition-all whitespace-nowrap {{ request()->routeIs($tab['route_pattern']) ? 'bg-primary text-primary-content font-bold shadow-md' : 'hover:bg-base-200 opacity-70' }}"
                >
                    <x-icon name="{{ $tab['icon'] }}" class="size-4" />
                    <span class="text-sm font-medium">{{ $tab['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Mobile: Fixed bottom navigation with icons only --}}
    <div class="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-base-100 border-t border-base-300 safe-area-inset-bottom">
        <nav class="flex items-center justify-around px-2 py-2">
            @foreach($tabs as $key => $tab)
                <a 
                    href="{{ route($tab['route']) }}" 
                    wire:navigate
                    class="flex flex-col items-center justify-center gap-1 px-3 py-1 rounded-lg transition-colors {{ request()->routeIs($tab['route_pattern']) ? 'text-primary' : 'opacity-60' }}"
                >
                    <x-icon :name="$tab['icon']" class="size-6" />
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
