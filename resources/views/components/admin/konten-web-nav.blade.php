@if(request()->routeIs('admin.school-profile.*') || 
    request()->routeIs('admin.news.*') || 
    request()->routeIs('admin.gallery.*') || 
    request()->routeIs('admin.programs.*') || 
    request()->routeIs('admin.contact-inquiries.*'))
    
    {{-- Desktop: Horizontal navbar below header --}}
    <flux:header class="hidden lg:block bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700 lg:px-6">
        <flux:navbar>
            <flux:navbar.item 
                icon="building-office-2" 
                :href="route('admin.school-profile.edit')" 
                :current="request()->routeIs('admin.school-profile.*')" 
                wire:navigate.hover
            >
                {{ __('Profil Sekolah') }}
            </flux:navbar.item>
            
            <flux:navbar.item 
                icon="newspaper" 
                :href="route('admin.news.index')" 
                :current="request()->routeIs('admin.news.*')" 
                wire:navigate.hover
            >
                {{ __('Berita & Artikel') }}
            </flux:navbar.item>
            
            <flux:navbar.item 
                icon="photo" 
                :href="route('admin.gallery.index')" 
                :current="request()->routeIs('admin.gallery.*')" 
                wire:navigate.hover
            >
                {{ __('Galeri') }}
            </flux:navbar.item>
            
            <flux:navbar.item 
                icon="academic-cap" 
                :href="route('admin.programs.index')" 
                :current="request()->routeIs('admin.programs.*')" 
                wire:navigate.hover
            >
                {{ __('Program Pendidikan') }}
            </flux:navbar.item>
            
            <flux:navbar.item 
                icon="envelope" 
                :href="route('admin.contact-inquiries.index')" 
                :current="request()->routeIs('admin.contact-inquiries.*')" 
                wire:navigate.hover
            >
                {{ __('Pesan Kontak') }}
            </flux:navbar.item>
        </flux:navbar>
    </flux:header>
    
    {{-- Mobile: Fixed bottom navigation with icons only --}}
    <div class="lg:hidden fixed bottom-0 left-0 right-0 z-50 bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-700 safe-area-inset-bottom">
        <nav class="flex items-center justify-around px-2 py-2">
            <a 
                href="{{ route('admin.school-profile.edit') }}" 
                wire:navigate
                class="flex flex-col items-center justify-center gap-1 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.school-profile.*') ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' }}"
            >
                <flux:icon.building-office-2 class="size-6" />
                <span class="text-xs font-medium">Profil</span>
            </a>
            
            <a 
                href="{{ route('admin.news.index') }}" 
                wire:navigate
                class="flex flex-col items-center justify-center gap-1 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.news.*') ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' }}"
            >
                <flux:icon.newspaper class="size-6" />
                <span class="text-xs font-medium">Berita</span>
            </a>
            
            <a 
                href="{{ route('admin.gallery.index') }}" 
                wire:navigate
                class="flex flex-col items-center justify-center gap-1 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.gallery.*') ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' }}"
            >
                <flux:icon.photo class="size-6" />
                <span class="text-xs font-medium">Galeri</span>
            </a>
            
            <a 
                href="{{ route('admin.programs.index') }}" 
                wire:navigate
                class="flex flex-col items-center justify-center gap-1 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.programs.*') ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' }}"
            >
                <flux:icon.academic-cap class="size-6" />
                <span class="text-xs font-medium">Program</span>
            </a>
            
            <a 
                href="{{ route('admin.contact-inquiries.index') }}" 
                wire:navigate
                class="flex flex-col items-center justify-center gap-1 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.contact-inquiries.*') ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' }}"
            >
                <flux:icon.envelope class="size-6" />
                <span class="text-xs font-medium">Pesan</span>
            </a>
        </nav>
    </div>
    
    {{-- Add bottom padding to main content on mobile to prevent content being hidden behind bottom nav --}}
    <style>
        @media (max-width: 1023px) {
            flux-main, [data-flux-main] {
                padding-bottom: 5rem !important;
            }
        }
        
        /* Safe area for devices with notch/home indicator */
        .safe-area-inset-bottom {
            padding-bottom: env(safe-area-inset-bottom);
        }
    </style>
@endif
