@php
    $isVisible = request()->routeIs('admin.school-profile.*')
        || request()->routeIs('admin.news.*')
        || request()->routeIs('admin.gallery.*')
        || request()->routeIs('admin.programs.*')
        || request()->routeIs('admin.contact-inquiries.*');

    $tabs = $isVisible ? [
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
    ] : [];
@endphp

<x-admin.sub-nav :tabs="$tabs">
    @if ($isVisible)
        <x-slot:actions>
            <x-ui.button 
                :label="__('Lihat Website')" 
                icon="o-arrow-top-right-on-square" 
                class="btn-ghost btn-sm text-primary font-bold uppercase tracking-widest text-[10px]" 
                link="/" 
                external 
                target="_blank" 
            />
        </x-slot:actions>
    @endif
</x-admin.sub-nav>
