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
<div class="lg:hidden fixed bottom-16 left-0 right-0 z-40 bg-base-100 border-t border-base-200 safe-area-inset-bottom">
    <nav class="flex items-center justify-around px-2 py-2">
        @foreach($tabs as $key => $tab)
            <a 
                href="{{ route($tab['route']) }}" 
                wire:navigate
                class="flex flex-col items-center justify-center gap-1 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs($tab['route_pattern']) ? 'text-primary bg-primary/10' : 'text-base-content/60 hover:text-base-content' }}"
            >
                <x-icon :name="$tab['icon']" class="size-6" />
                <span class="text-xs font-medium">{{ $tab['label_short'] }}</span>
            </a>
        @endforeach
    </nav>
</div>

<style>
    /* Adjust main content padding on mobile for footer nav */
    @media (max-width: 1023px) {
        .mary-content {
            padding-bottom: 8rem !important;
        }
    }
    
    .safe-area-inset-bottom {
        padding-bottom: env(safe-area-inset-bottom);
    }
</style>
@endif
