<!-- Footer -->
@php
    $schoolProfile = app(\App\Services\CacheService::class)->getSchoolProfile();
    $name = $schoolProfile?->name ?? config('app.name');
    $phone = $schoolProfile?->phone ?? '6281234567890';
    $email = $schoolProfile?->email ?? 'info@simkopkbm.com';
    $address = $schoolProfile?->address ?? 'Jakarta, Indonesia';
    $facebook = $schoolProfile?->facebook_url ?? '#';
    $twitter = $schoolProfile?->twitter_url ?? '#';
    $instagram = $schoolProfile?->instagram_url ?? '#';
    $youtube = $schoolProfile?->youtube_url ?? '#';
@endphp
<footer class="bg-slate-900 text-white relative overflow-hidden border-t border-slate-800">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-5">
        <svg class="w-full h-full" viewBox="0 0 100 100" fill="none">
            <defs>
                <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                    <path d="M 10 0 L 0 0 0 10" fill="none" stroke="currentColor" stroke-width="0.5"/>
                </pattern>
            </defs>
            <rect width="100" height="100" fill="url(#grid)" />
        </svg>
    </div>
    
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            <!-- About Section -->
            <div class="sm:col-span-2 lg:col-span-2">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="flex items-center justify-center">
                        <x-global.app-logo-icon class="w-10 h-10 text-amber-500" />
                    </div>
                    <div>
                        <h3 class="text-xl font-bold font-heading text-white tracking-tight">{{ $name }}</h3>
                        <p class="text-slate-400 text-sm uppercase tracking-wide">Pusat Kegiatan Belajar Masyarakat</p>
                    </div>
                </div>
                <p class="text-slate-400 text-sm leading-relaxed max-w-md mb-6">
                    Berkomitmen memberikan pendidikan berkualitas dan terjangkau untuk semua lapisan masyarakat. 
                    Kami hadir untuk mewujudkan impian pendidikan yang lebih baik.
                </p>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ $facebook }}" target="{{ $facebook !== '#' ? '_blank' : '_self' }}" class="w-10 h-10 bg-slate-800 hover:bg-amber-500 hover:text-white text-slate-400 rounded-lg flex items-center justify-center transition-all duration-200 shadow-lg hover:shadow-amber-500/20 hover:-translate-y-1">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/>
                        </svg>
                    </a>
                    <a href="{{ $twitter }}" target="{{ $twitter !== '#' ? '_blank' : '_self' }}" class="w-10 h-10 bg-slate-800 hover:bg-amber-500 hover:text-white text-slate-400 rounded-lg flex items-center justify-center transition-all duration-200 shadow-lg hover:shadow-amber-500/20 hover:-translate-y-1">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M22.46 6c-.77.35-1.6.58-2.46.69.88-.53 1.56-1.37 1.88-2.38-.83.5-1.75.85-2.72 1.05C18.37 4.5 17.26 4 16 4c-2.35 0-4.27 1.92-4.27 4.29 0 .34.04.67.11.98C8.28 9.09 5.11 7.38 3 4.79c-.37.63-.58 1.37-.58 2.15 0 1.49.75 2.81 1.91 3.56-.71 0-1.37-.2-1.95-.5v.03c0 2.08 1.48 3.82 3.44 4.21a4.22 4.22 0 0 1-1.93.07 4.28 4.28 0 0 0 4 2.98 8.521 8.521 0 0 1-5.33 1.84c-.34 0-.68-.02-1.02-.06C3.44 20.29 5.7 21 8.12 21 16 21 20.33 14.46 20.33 8.79c0-.19 0-.37-.01-.56.84-.6 1.56-1.36 2.14-2.23z"/>
                        </svg>
                    </a>
                    <a href="{{ $instagram }}" target="{{ $instagram !== '#' ? '_blank' : '_self' }}" class="w-10 h-10 bg-slate-800 hover:bg-amber-500 hover:text-white text-slate-400 rounded-lg flex items-center justify-center transition-all duration-200 shadow-lg hover:shadow-amber-500/20 hover:-translate-y-1">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.174-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.402.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.357-.629-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146 1.124.347 2.317.544 3.571.544 6.624 0 11.99-5.367 11.99-11.988C24.5 5.896 19.354.75 12.5.75z"/>
                        </svg>
                    </a>
                    <a href="{{ $youtube }}" target="{{ $youtube !== '#' ? '_blank' : '_self' }}" class="w-10 h-10 bg-slate-800 hover:bg-amber-500 hover:text-white text-slate-400 rounded-lg flex items-center justify-center transition-all duration-200 shadow-lg hover:shadow-amber-500/20 hover:-translate-y-1">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12.5.75C6.146.75 1 5.896 1 12.25c0 5.089 3.292 9.387 7.863 10.91-.11-.937-.227-2.482.025-3.566.217-.932 1.405-5.956 1.405-5.956s-.359-.719-.359-1.782c0-1.668.967-2.914 2.171-2.914 1.023 0 1.518.769 1.518 1.69 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.402.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.357-.629-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146 1.124.347 2.317.544 3.571.544 6.624 0 11.99-5.367 11.99-11.988C24.5 5.896 19.354.75 12.5.75z"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Quick Links -->
            <div>
                <h3 class="text-lg font-bold font-heading mb-6 text-white border-b-2 border-amber-500 inline-block pb-1">Tautan Cepat</h3>
                <ul class="space-y-3">
                    <li><a href="{{ route('public.about') }}" class="text-slate-400 hover:text-white transition-colors duration-200 flex items-center group text-sm">
                        <svg class="w-4 h-4 mr-2 group-hover:translate-x-1 group-hover:text-amber-400 transition-all duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        Tentang Kami
                    </a></li>
                    <li><a href="{{ route('public.programs.index') }}" class="text-slate-400 hover:text-white transition-colors duration-200 flex items-center group text-sm">
                        <svg class="w-4 h-4 mr-2 group-hover:translate-x-1 group-hover:text-amber-400 transition-all duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        Program Pendidikan
                    </a></li>
                    <li><a href="{{ route('public.news.index') }}" class="text-slate-400 hover:text-white transition-colors duration-200 flex items-center group text-sm">
                        <svg class="w-4 h-4 mr-2 group-hover:translate-x-1 group-hover:text-amber-400 transition-all duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        Berita
                    </a></li>
                    <li><a href="{{ route('public.gallery') }}" class="text-slate-400 hover:text-white transition-colors duration-200 flex items-center group text-sm">
                        <svg class="w-4 h-4 mr-2 group-hover:translate-x-1 group-hover:text-amber-400 transition-all duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        Galeri
                    </a></li>
                    <li><a href="{{ route('public.contact') }}" class="text-slate-400 hover:text-white transition-colors duration-200 flex items-center group text-sm">
                        <svg class="w-4 h-4 mr-2 group-hover:translate-x-1 group-hover:text-amber-400 transition-all duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        Kontak
                    </a></li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div>
                <h3 class="text-lg font-bold font-heading mb-6 text-white border-b-2 border-amber-500 inline-block pb-1">Hubungi Kami</h3>
                <ul class="space-y-4">
                    <li class="flex items-start">
                        <div class="w-10 h-10 bg-slate-800 rounded-lg flex items-center justify-center text-amber-500 mt-0.5 mr-3 shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-slate-400 text-xs">Email</p>
                            <p class="text-white text-sm font-medium">{{ $email }}</p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <div class="w-10 h-10 bg-slate-800 rounded-lg flex items-center justify-center text-amber-500 mt-0.5 mr-3 shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-slate-400 text-xs">Telepon</p>
                            <p class="text-white text-sm font-medium">{{ $phone }}</p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <div class="w-10 h-10 bg-slate-800 rounded-lg flex items-center justify-center text-amber-500 mt-0.5 mr-3 shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-slate-400 text-xs">Alamat</p>
                            <p class="text-white text-sm font-medium max-w-[200px]">{{ $address }}</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <div class="border-t border-slate-800 mt-12 pt-8 flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0">
            <p class="text-slate-400 text-sm text-center sm:text-left">
                &copy; {{ date('Y') }} {{ $name }}. Semua hak dilindungi.
            </p>
            <div class="flex items-center space-x-4">
                <a href="#" class="text-slate-400 hover:text-white text-sm transition-colors duration-200 hover:underline">Kebijakan Privasi</a>
                <span class="text-slate-700">•</span>
                <a href="#" class="text-slate-400 hover:text-white text-sm transition-colors duration-200 hover:underline">Syarat & Ketentuan</a>
            </div>
        </div>
    </div>
</footer>