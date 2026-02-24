<!-- Top Bar -->
@php
    $schoolProfile = app(\App\Services\CacheService::class)->getSchoolProfile();
    $phone = $schoolProfile?->phone ?? '6281234567890';
    $email = $schoolProfile?->email ?? 'info@simkopkbm.com';
    $facebook = $schoolProfile?->facebook_url ?? '#';
    $instagram = $schoolProfile?->instagram_url ?? '#';
@endphp
<div class="bg-slate-900 text-white py-2 hidden lg:block border-b border-white/10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center text-xs font-medium">
        <div class="flex items-center space-x-6">
            <a href="tel:+{{ preg_replace('/[^0-9]/', '', $phone) }}" class="flex items-center space-x-2 text-slate-300 hover:text-amber-400 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
                <span>+{{ preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $phone)) }}</span>
            </a>
            <a href="mailto:{{ $email }}" class="flex items-center space-x-2 text-slate-300 hover:text-amber-400 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <span>{{ $email }}</span>
            </a>
        </div>
        <div class="flex items-center space-x-4">
             <a href="{{ $facebook }}" target="{{ $facebook !== '#' ? '_blank' : '_self' }}" class="text-slate-300 hover:text-amber-400 transition-colors" aria-label="Facebook">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
             </a>
             <a href="{{ $instagram }}" target="{{ $instagram !== '#' ? '_blank' : '_self' }}" class="text-slate-300 hover:text-amber-400 transition-colors" aria-label="Instagram">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
             </a>
        </div>
    </div>
</div>

<!-- Navigation -->
<nav class="bg-slate-900/95 backdrop-blur-md shadow-xl border-b border-white/10 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20">
            <!-- Logo -->
            <div class="flex-shrink-0 flex items-center h-full">
                <a href="{{ route('home') }}" class="flex items-center space-x-3 group">
                    <x-global.app-logo-icon class="w-10 h-10 rounded-lg shadow-lg group-hover:shadow-amber-500/50 transition-all duration-300 group-hover:scale-105 object-contain" />
                    <div class="hidden sm:block">
                        <h1 class="text-xl font-heading font-bold text-white tracking-tight group-hover:text-amber-400 transition-colors">
                            {{ config('app.name') }}
                        </h1>
                        <p class="text-[0.65rem] uppercase tracking-wider text-slate-400 font-semibold group-hover:text-white transition-colors">Pusat Kegiatan Belajar Masyarakat</p>
                    </div>
                </a>
            </div>
            
            <!-- Desktop Navigation -->
            <div class="hidden lg:flex lg:space-x-8 lg:h-full lg:flex-1 lg:justify-center">
                <a href="{{ route('home') }}" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-semibold transition-all duration-200 h-full {{ request()->routeIs('home') ? 'border-amber-400 text-amber-400' : 'border-transparent text-slate-300 hover:text-white hover:border-slate-300' }}" wire:navigate>
                    Beranda
                </a>
                
                <!-- About Dropdown -->
                <div class="relative h-full flex items-center" x-data="{ open: false }">
                    <button @click="open = !open" @click.away="open = false" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-semibold transition-all duration-200 h-full {{ request()->routeIs('public.about*', 'public.organizational-structure', 'public.facilities') ? 'border-amber-400 text-amber-400' : 'border-transparent text-slate-300 hover:text-white hover:border-slate-300' }}">
                        Tentang Kami
                        <svg class="ml-1 h-4 w-4 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" 
                            style="display: none;"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="absolute left-1/2 -translate-x-1/2 top-full mt-1 w-56 rounded-xl bg-slate-800 border border-slate-700 shadow-xl z-[9999]">
                        <div class="py-2">
                            <a href="{{ route('public.about') }}" @click="open = false" class="flex items-center px-4 py-3 text-sm text-slate-300 hover:bg-slate-700 hover:text-amber-400 transition-colors duration-150" wire:navigate>
                                <svg class="w-4 h-4 mr-3 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Profil Sekolah
                            </a>
                            <a href="{{ route('public.organizational-structure') }}" @click="open = false" class="flex items-center px-4 py-3 text-sm text-slate-300 hover:bg-slate-700 hover:text-amber-400 transition-colors duration-150" wire:navigate>
                                <svg class="w-4 h-4 mr-3 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                Struktur Organisasi
                            </a>
                            <a href="{{ route('public.facilities') }}" @click="open = false" class="flex items-center px-4 py-3 text-sm text-slate-300 hover:bg-slate-700 hover:text-amber-400 transition-colors duration-150" wire:navigate>
                                <svg class="w-4 h-4 mr-3 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                Fasilitas
                            </a>
                        </div>
                    </div>
                </div>
                
                <a href="{{ route('public.programs.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-semibold transition-all duration-200 h-full {{ request()->routeIs('public.programs*') ? 'border-amber-400 text-amber-400' : 'border-transparent text-slate-300 hover:text-white hover:border-slate-300' }}" wire:navigate>
                    Program
                </a>
                <a href="{{ route('public.news.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-semibold transition-all duration-200 h-full {{ request()->routeIs('public.news*') ? 'border-amber-400 text-amber-400' : 'border-transparent text-slate-300 hover:text-white hover:border-slate-300' }}" wire:navigate>
                    Berita
                </a>
                <a href="{{ route('public.gallery') }}" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-semibold transition-all duration-200 h-full {{ request()->routeIs('public.gallery') ? 'border-amber-400 text-amber-400' : 'border-transparent text-slate-300 hover:text-white hover:border-slate-300' }}" wire:navigate>
                    Galeri
                </a>
            </div>

            <div class="hidden lg:flex lg:items-center lg:space-x-4 flex-shrink-0">
                <!-- Admin Shortcut -->
                @auth
                    @php
                        $dashboardRoute = auth()->user()->role === 'guru' ? route('teacher.dashboard') : route('dashboard');
                    @endphp
                    <a href="{{ $dashboardRoute }}" class="inline-flex items-center px-6 py-2.5 rounded-full text-sm font-bold border-2 border-slate-700 text-slate-300 hover:bg-slate-800 hover:text-white hover:border-amber-500 transition-all duration-200 transform hover:-translate-y-0.5">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center px-6 py-2.5 rounded-full text-sm font-bold border-2 border-slate-700 text-slate-300 hover:bg-slate-800 hover:text-white hover:border-amber-500 transition-all duration-200 transform hover:-translate-y-0.5">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                        </svg>
                        Login Admin
                    </a>
                @endauth

                <!-- CTA Button -->
                <a href="{{ route('public.register') }}" class="inline-flex items-center px-6 py-2.5 rounded-full text-sm font-bold bg-gradient-to-r from-amber-500 to-amber-600 text-white shadow-lg hover:shadow-amber-500/30 hover:from-amber-600 hover:to-amber-700 transition-all duration-200 transform hover:-translate-y-0.5" wire:navigate>
                    Daftar Sekarang
                </a>
            </div>

            <!-- Mobile menu button -->
            <div class="flex items-center lg:hidden" x-data="{ mobileMenuOpen: false }">
                <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="inline-flex items-center justify-center p-2 rounded-lg text-slate-300 hover:text-white hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-amber-500 transition-colors duration-200">
                    <span class="sr-only">Buka menu</span>
                    <svg class="h-6 w-6" :class="{ 'hidden': mobileMenuOpen, 'block': !mobileMenuOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg class="h-6 w-6" :class="{ 'block': mobileMenuOpen, 'hidden': !mobileMenuOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                
                <!-- Mobile menu -->
                <div x-show="mobileMenuOpen" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     @click.away="mobileMenuOpen = false"
                     class="absolute top-full left-0 right-0 bg-slate-900 border-t border-slate-800 shadow-xl z-50 lg:hidden max-h-[90vh] overflow-y-auto">
                    <div class="px-4 pt-2 pb-6 space-y-1">
                        <a href="{{ route('home') }}" class="flex items-center px-3 py-3 rounded-lg text-base font-medium {{ request()->routeIs('home') ? 'bg-white/10 text-amber-400 border-l-4 border-amber-500' : 'text-slate-300 hover:bg-white/5 hover:text-white' }} transition-colors duration-200" wire:navigate>
                           Beranda
                        </a>
                        <a href="{{ route('public.about') }}" class="flex items-center px-3 py-3 rounded-lg text-base font-medium {{ request()->routeIs('public.about') ? 'bg-white/10 text-amber-400 border-l-4 border-amber-500' : 'text-slate-300 hover:bg-white/5 hover:text-white' }} transition-colors duration-200" wire:navigate>
                           Profil Sekolah
                        </a>
                        <a href="{{ route('public.organizational-structure') }}" class="flex items-center px-3 py-3 rounded-lg text-base font-medium {{ request()->routeIs('public.organizational-structure') ? 'bg-white/10 text-amber-400 border-l-4 border-amber-500' : 'text-slate-300 hover:bg-white/5 hover:text-white' }} transition-colors duration-200" wire:navigate>
                           Struktur Organisasi
                        </a>
                        <a href="{{ route('public.facilities') }}" class="flex items-center px-3 py-3 rounded-lg text-base font-medium {{ request()->routeIs('public.facilities') ? 'bg-white/10 text-amber-400 border-l-4 border-amber-500' : 'text-slate-300 hover:bg-white/5 hover:text-white' }} transition-colors duration-200" wire:navigate>
                           Fasilitas
                        </a>
                        <a href="{{ route('public.programs.index') }}" class="flex items-center px-3 py-3 rounded-lg text-base font-medium {{ request()->routeIs('public.programs*') ? 'bg-white/10 text-amber-400 border-l-4 border-amber-500' : 'text-slate-300 hover:bg-white/5 hover:text-white' }} transition-colors duration-200" wire:navigate>
                           Program Pendidikan
                        </a>
                        <a href="{{ route('public.news.index') }}" class="flex items-center px-3 py-3 rounded-lg text-base font-medium {{ request()->routeIs('public.news*') ? 'bg-white/10 text-amber-400 border-l-4 border-amber-500' : 'text-slate-300 hover:bg-white/5 hover:text-white' }} transition-colors duration-200" wire:navigate>
                           Berita
                        </a>
                        <a href="{{ route('public.gallery') }}" class="flex items-center px-3 py-3 rounded-lg text-base font-medium {{ request()->routeIs('public.gallery') ? 'bg-white/10 text-amber-400 border-l-4 border-amber-500' : 'text-slate-300 hover:bg-white/5 hover:text-white' }} transition-colors duration-200" wire:navigate>
                           Galeri
                        </a>
                        <div class="pt-4 mt-4 border-t border-slate-700 space-y-3">
                            <a href="{{ route('public.register') }}" class="flex items-center justify-center px-3 py-3 rounded-full text-base font-bold bg-amber-500 text-white shadow-lg mx-3 hover:bg-amber-600 transition-colors" wire:navigate>
                                Daftar Sekarang
                            </a>
                            
                            @auth
                                @php
                                    $dashboardRoute = auth()->user()->role === 'guru' ? route('teacher.dashboard') : route('dashboard');
                                @endphp
                                <a href="{{ $dashboardRoute }}" class="flex items-center justify-center px-3 py-3 rounded-full text-base font-bold border-2 border-slate-700 text-slate-300 mx-3 hover:bg-white/5 hover:text-white hover:border-amber-400 transition-all">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                    </svg>
                                    Dashboard
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="flex items-center justify-center px-3 py-3 rounded-full text-base font-bold border-2 border-slate-700 text-slate-300 mx-3 hover:bg-white/5 hover:text-white hover:border-amber-400 transition-all">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                    </svg>
                                    Login Admin
                                </a>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>