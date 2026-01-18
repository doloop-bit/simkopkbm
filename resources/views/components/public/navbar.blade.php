<!-- Navigation -->
<nav class="bg-white/80 backdrop-blur-md shadow-lg border-b border-green-100 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20">
            <div class="flex items-center">
                <!-- Logo -->
                <div class="flex-shrink-0 flex items-center">
                    <a href="{{ route('home') }}" class="flex items-center space-x-3 group">
                        <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all duration-300 group-hover:scale-105">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <div class="hidden sm:block">
                            <h1 class="text-xl font-bold bg-gradient-to-r from-green-700 to-emerald-600 bg-clip-text text-transparent">
                                {{ config('app.name') }}
                            </h1>
                            <p class="text-xs text-green-600 font-medium">Pusat Kegiatan Belajar Masyarakat</p>
                        </div>
                    </a>
                </div>
                
                <!-- Desktop Navigation -->
                <div class="hidden lg:ml-12 lg:flex lg:space-x-1">
                    <a href="{{ route('home') }}" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('home') ? 'bg-green-100 text-green-800 shadow-sm' : 'text-gray-700 hover:text-green-700 hover:bg-green-50' }}" wire:navigate>
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Beranda
                    </a>
                    
                    <!-- About Dropdown -->
                    <div class="relative z-[9999]" x-data="{ open: false }">
                        <button @click="open = !open" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('public.about*', 'public.organizational-structure', 'public.facilities') ? 'bg-green-100 text-green-800 shadow-sm' : 'text-gray-700 hover:text-green-700 hover:bg-green-50' }}">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            Tentang Kami
                            <svg class="ml-1 h-4 w-4 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" @click.away="open = false" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute left-0 mt-2 w-56 rounded-xl bg-white shadow-xl ring-1 ring-green-100 border border-green-50 z-[9999]">
                            <div class="py-2">
                                <a href="{{ route('public.about') }}" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-green-50 hover:text-green-700 transition-colors duration-150" wire:navigate>
                                    <svg class="w-4 h-4 mr-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Profil Sekolah
                                </a>
                                <a href="{{ route('public.organizational-structure') }}" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-green-50 hover:text-green-700 transition-colors duration-150" wire:navigate>
                                    <svg class="w-4 h-4 mr-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    Struktur Organisasi
                                </a>
                                <a href="{{ route('public.facilities') }}" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-green-50 hover:text-green-700 transition-colors duration-150" wire:navigate>
                                    <svg class="w-4 h-4 mr-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    Fasilitas
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <a href="{{ route('public.programs.index') }}" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('public.programs*') ? 'bg-green-100 text-green-800 shadow-sm' : 'text-gray-700 hover:text-green-700 hover:bg-green-50' }}" wire:navigate>
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        Program
                    </a>
                    <a href="{{ route('public.news.index') }}" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('public.news*') ? 'bg-green-100 text-green-800 shadow-sm' : 'text-gray-700 hover:text-green-700 hover:bg-green-50' }}" wire:navigate>
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                        </svg>
                        Berita
                    </a>
                    <a href="{{ route('public.gallery') }}" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('public.gallery') ? 'bg-green-100 text-green-800 shadow-sm' : 'text-gray-700 hover:text-green-700 hover:bg-green-50' }}" wire:navigate>
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Galeri
                    </a>
                    <a href="{{ route('public.contact') }}" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium bg-gradient-to-r from-green-600 to-emerald-600 text-white shadow-lg hover:shadow-xl hover:from-green-700 hover:to-emerald-700 transition-all duration-200 transform hover:scale-105" wire:navigate>
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        Kontak
                    </a>
                </div>
            </div>

            <!-- Mobile menu button -->
            <div class="flex items-center lg:hidden" x-data="{ mobileMenuOpen: false }">
                <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="inline-flex items-center justify-center p-2 rounded-lg text-gray-600 hover:text-green-700 hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-green-500 transition-colors duration-200">
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
                     class="fixed inset-x-0 top-20 bg-white border-t border-green-100 shadow-lg z-50 lg:hidden">
                    <div class="px-4 pt-2 pb-3 space-y-1">
                        <a href="{{ route('home') }}" class="flex items-center px-3 py-3 rounded-lg text-base font-medium {{ request()->routeIs('home') ? 'bg-green-100 text-green-800 border-l-4 border-green-500' : 'text-gray-700 hover:bg-green-50 hover:text-green-700' }} transition-colors duration-200" wire:navigate>
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            Beranda
                        </a>
                        <a href="{{ route('public.about') }}" class="flex items-center px-3 py-3 rounded-lg text-base font-medium {{ request()->routeIs('public.about') ? 'bg-green-100 text-green-800 border-l-4 border-green-500' : 'text-gray-700 hover:bg-green-50 hover:text-green-700' }} transition-colors duration-200" wire:navigate>
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Profil Sekolah
                        </a>
                        <a href="{{ route('public.organizational-structure') }}" class="flex items-center px-3 py-3 rounded-lg text-base font-medium {{ request()->routeIs('public.organizational-structure') ? 'bg-green-100 text-green-800 border-l-4 border-green-500' : 'text-gray-700 hover:bg-green-50 hover:text-green-700' }} transition-colors duration-200" wire:navigate>
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            Struktur Organisasi
                        </a>
                        <a href="{{ route('public.facilities') }}" class="flex items-center px-3 py-3 rounded-lg text-base font-medium {{ request()->routeIs('public.facilities') ? 'bg-green-100 text-green-800 border-l-4 border-green-500' : 'text-gray-700 hover:bg-green-50 hover:text-green-700' }} transition-colors duration-200" wire:navigate>
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            Fasilitas
                        </a>
                        <a href="{{ route('public.programs.index') }}" class="flex items-center px-3 py-3 rounded-lg text-base font-medium {{ request()->routeIs('public.programs*') ? 'bg-green-100 text-green-800 border-l-4 border-green-500' : 'text-gray-700 hover:bg-green-50 hover:text-green-700' }} transition-colors duration-200" wire:navigate>
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            Program Pendidikan
                        </a>
                        <a href="{{ route('public.news.index') }}" class="flex items-center px-3 py-3 rounded-lg text-base font-medium {{ request()->routeIs('public.news*') ? 'bg-green-100 text-green-800 border-l-4 border-green-500' : 'text-gray-700 hover:bg-green-50 hover:text-green-700' }} transition-colors duration-200" wire:navigate>
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                            </svg>
                            Berita
                        </a>
                        <a href="{{ route('public.gallery') }}" class="flex items-center px-3 py-3 rounded-lg text-base font-medium {{ request()->routeIs('public.gallery') ? 'bg-green-100 text-green-800 border-l-4 border-green-500' : 'text-gray-700 hover:bg-green-50 hover:text-green-700' }} transition-colors duration-200" wire:navigate>
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Galeri
                        </a>
                        <a href="{{ route('public.contact') }}" class="flex items-center px-3 py-3 rounded-lg text-base font-medium bg-gradient-to-r from-green-600 to-emerald-600 text-white shadow-lg" wire:navigate>
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            Kontak
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>