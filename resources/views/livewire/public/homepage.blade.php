<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Storage;
use App\Models\{NewsArticle, Program, GalleryPhoto, SchoolProfile};
use App\Services\CacheService;

new #[Layout('components.public.layouts.public')] class extends Component {
    public function with(): array
    {
        $cacheService = app(CacheService::class);
        
        return [
            'schoolProfile' => $cacheService->getSchoolProfile(),
            'latestNews' => $cacheService->getLatestNews(3),
            'programs' => $cacheService->getActivePrograms(),
            'featuredPhotos' => $cacheService->getFeaturedPhotos(6),
            'title' => 'Beranda - ' . config('app.name'),
            'description' => 'Selamat datang di ' . config('app.name') . ' - Pusat Kegiatan Belajar Masyarakat yang menyediakan pendidikan berkualitas untuk semua. Temukan program PAUD, Paket A, B, dan C.',
            'keywords' => 'PKBM, Pusat Kegiatan Belajar Masyarakat, Pendidikan, PAUD, Paket A, Paket B, Paket C, Sekolah, Beranda',
            'ogType' => 'website',
            'ogTitle' => config('app.name') . ' - Pusat Kegiatan Belajar Masyarakat',
            'ogDescription' => 'Selamat datang di ' . config('app.name') . ' - Pusat Kegiatan Belajar Masyarakat yang menyediakan pendidikan berkualitas untuk semua.',
        ];
    }
}; ?>

<div>
    <!-- Hero Section -->
    <div class="relative bg-slate-900 text-white overflow-hidden">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-20">
            <svg class="w-full h-full" viewBox="0 0 100 100" fill="none">
                <defs>
                    <pattern id="hero-grid" width="20" height="20" patternUnits="userSpaceOnUse">
                        <path d="M 20 0 L 0 0 0 20" fill="none" stroke="currentColor" stroke-width="0.5"/>
                    </pattern>
                </defs>
                <rect width="100" height="100" fill="url(#hero-grid)" />
            </svg>
        </div>
        

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20 lg:py-24">
            <div class="text-center">
                @if($schoolProfile && $schoolProfile->logo_path)
                    <div class="mb-6 sm:mb-8">
                        <img src="{{ Storage::url($schoolProfile->logo_path) }}" alt="{{ $schoolProfile->name }}" class="h-24 sm:h-28 lg:h-32 mx-auto drop-shadow-2xl">
                    </div>
                @endif
                <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl xl:text-7xl font-bold font-heading mb-4 sm:mb-6 leading-tight">
                    <span class="bg-gradient-to-r from-white to-slate-300 bg-clip-text text-transparent">
                        {{ $schoolProfile?->name ?? config('app.name') }}
                    </span>
                </h1>
                <p class="text-lg sm:text-xl md:text-2xl lg:text-3xl mb-6 sm:mb-8 text-slate-400 font-light">
                    Pusat Kegiatan Belajar Masyarakat
                </p>
                @if($schoolProfile && $schoolProfile->vision)
                    <p class="text-base sm:text-lg md:text-xl max-w-2xl lg:max-w-4xl mx-auto text-slate-300 leading-relaxed mb-8 sm:mb-10 lg:mb-12 px-4">
                        {{ Str::limit($schoolProfile->vision, 200) }}
                    </p>
                @endif
                <div class="flex flex-col sm:flex-row gap-4 sm:gap-6 justify-center items-center px-4">
                    <a href="{{ route('public.programs.index') }}" class="group inline-flex items-center justify-center px-6 sm:px-8 py-3 sm:py-4 bg-amber-500 text-white font-bold rounded-xl shadow-xl hover:shadow-2xl hover:shadow-amber-500/20 transition-all duration-300 transform hover:scale-105 hover:bg-amber-600 w-full sm:w-auto">
                        <svg class="w-5 h-5 mr-2 group-hover:translate-x-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        Lihat Program
                    </a>
                    <a href="{{ route('public.register') }}" class="group inline-flex items-center justify-center px-6 sm:px-8 py-3 sm:py-4 border-2 border-white/20 text-white font-semibold rounded-xl hover:bg-white/10 transition-all duration-300 transform hover:scale-105 w-full sm:w-auto">
                        <svg class="w-5 h-5 mr-2 group-hover:translate-x-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                        Daftar Sekarang
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Wave Bottom -->
        <div class="absolute bottom-0 left-0 right-0">
            <svg viewBox="0 0 1440 120" fill="none" class="w-full h-auto">
                <path d="M0,64L48,69.3C96,75,192,85,288,80C384,75,480,53,576,48C672,43,768,53,864,64C960,75,1056,85,1152,80C1248,75,1344,53,1392,42.7L1440,32L1440,120L1392,120C1344,120,1248,120,1152,120C1056,120,960,120,864,120C768,120,672,120,576,120C480,120,384,120,288,120C192,120,96,120,48,120L0,120Z" fill="rgb(249 250 251)"/>
            </svg>
        </div>
    </div>

    <!-- Latest News Section -->
    @if($latestNews->count() > 0)
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold font-heading text-slate-900 mb-4">Berita Terbaru</h2>
                <p class="text-lg text-slate-600 max-w-2xl mx-auto">
                    Ikuti perkembangan terbaru dan kegiatan menarik di PKBM kami
                </p>
                <div class="w-24 h-1 bg-amber-500 mx-auto mt-6 rounded-full"></div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
                @foreach($latestNews as $article)
                    <article class="group bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden transform hover:-translate-y-2">
                        <div class="relative overflow-hidden">
                            @if($article->featured_image_path)
                                <img src="{{ Storage::url($article->featured_image_path) }}" alt="{{ $article->title }}" class="w-full h-48 sm:h-56 object-cover group-hover:scale-110 transition-transform duration-500">
                            @else
                                <div class="w-full h-48 sm:h-56 bg-slate-100 flex items-center justify-center">
                                    <svg class="w-12 sm:w-16 h-12 sm:h-16 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            @endif
                            <div class="absolute top-4 left-4">
                                <span class="bg-amber-500 text-white px-3 py-1 rounded-full text-sm font-medium">
                                    {{ $article->published_at->format('d M Y') }}
                                </span>
                            </div>
                        </div>
                        <div class="p-4 sm:p-6">
                            <h3 class="text-lg sm:text-xl font-bold font-heading text-slate-900 mb-3 line-clamp-2 group-hover:text-amber-600 transition-colors duration-200">
                                {{ $article->title }}
                            </h3>
                            <p class="text-slate-600 mb-4 line-clamp-3 leading-relaxed text-sm sm:text-base">
                                {{ $article->excerpt ?? Str::limit(strip_tags($article->content), 120) }}
                            </p>
                            <a href="{{ route('public.news.show', $article->slug) }}" class="inline-flex items-center text-amber-600 hover:text-amber-700 font-semibold group text-sm sm:text-base">
                                Baca Selengkapnya
                                <svg class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="text-center mt-12">
                <a href="{{ route('public.news.index') }}" class="inline-flex items-center justify-center px-8 py-4 bg-slate-800 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl hover:bg-slate-700 transition-all duration-300 transform hover:scale-105">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                    </svg>
                    Lihat Semua Berita
                </a>
            </div>
        </div>
    @endif

    <!-- Programs Section -->
    @if($programs->count() > 0)
        <div class="bg-white py-20 border-t border-slate-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-bold font-heading text-slate-900 mb-4">Program Pendidikan</h2>
                    <p class="text-lg text-slate-600 max-w-3xl mx-auto">
                        Kami menawarkan berbagai program pendidikan berkualitas yang disesuaikan dengan kebutuhan dan perkembangan masyarakat
                    </p>
                    <div class="w-24 h-1 bg-amber-500 mx-auto mt-6 rounded-full"></div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8">
                    @foreach($programs as $program)
                        <div class="group bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden transform hover:-translate-y-2">
                            <div class="relative overflow-hidden">
                                @if($program->image_path)
                                    <img src="{{ Storage::url($program->image_path) }}" alt="{{ $program->name }}" class="w-full h-40 sm:h-48 object-cover group-hover:scale-110 transition-transform duration-500">
                                @else
                                    <div class="w-full h-40 sm:h-48 bg-slate-800 flex items-center justify-center relative overflow-hidden">
                                        <div class="absolute inset-0 bg-black opacity-10"></div>
                                        <span class="relative text-white text-2xl sm:text-3xl font-bold drop-shadow-lg font-heading">{{ Str::upper(Str::substr($program->name, 0, 1)) }}</span>
                                        <!-- Decorative circles -->
                                        <div class="absolute top-4 right-4 w-6 sm:w-8 h-6 sm:h-8 bg-white opacity-10 rounded-full"></div>
                                        <div class="absolute bottom-4 left-4 w-4 sm:w-6 h-4 sm:h-6 bg-white opacity-10 rounded-full"></div>
                                    </div>
                                @endif
                                <div class="absolute top-4 left-4">
                                    <span class="bg-white/90 backdrop-blur-sm text-slate-900 px-2 sm:px-3 py-1 rounded-full text-xs sm:text-sm font-semibold border border-slate-200">
                                        {{ Str::upper($program->level) }}
                                    </span>
                                </div>
                            </div>
                            <div class="p-4 sm:p-6">
                                <h3 class="text-lg sm:text-xl font-bold text-slate-900 mb-3 group-hover:text-amber-600 transition-colors duration-200">
                                    {{ $program->name }}
                                </h3>
                                <p class="text-slate-600 text-sm mb-4 sm:mb-6 line-clamp-3 leading-relaxed">
                                    {{ Str::limit($program->description, 100) }}
                                </p>
                                <a href="{{ route('public.programs.show', $program->slug) }}" class="inline-flex items-center text-amber-600 hover:text-amber-700 font-semibold group text-sm sm:text-base">
                                    Selengkapnya
                                    <svg class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="text-center mt-12">
                    <a href="{{ route('public.programs.index') }}" class="inline-flex items-center justify-center px-8 py-4 bg-slate-800 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl hover:bg-slate-700 transition-all duration-300 transform hover:scale-105">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        Lihat Semua Program
                    </a>
                </div>
            </div>
        </div>
    @endif

    <!-- Gallery Preview Section -->
    @if($featuredPhotos->count() > 0)
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold font-heading text-slate-900 mb-4">Galeri Foto</h2>
                <p class="text-lg text-slate-600 max-w-2xl mx-auto">
                    Dokumentasi kegiatan dan momen berharga dalam perjalanan pendidikan di PKBM kami
                </p>
                <div class="w-24 h-1 bg-amber-500 mx-auto mt-6 rounded-full"></div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
                @foreach($featuredPhotos as $photo)
                    <a href="{{ route('public.gallery') }}" class="group relative overflow-hidden rounded-xl sm:rounded-2xl aspect-square shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:scale-105">
                        <x-global.optimized-image
                            :src="Storage::url($photo->thumbnail_path)"
                            :webp-src="$photo->thumbnail_webp_path ? Storage::url($photo->thumbnail_webp_path) : null"
                            :alt="$photo->title"
                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                            :lazy="true"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        <div class="absolute bottom-0 left-0 right-0 p-2 sm:p-4 text-white transform translate-y-full group-hover:translate-y-0 transition-transform duration-300">
                            <h3 class="font-semibold text-xs sm:text-sm mb-1">{{ $photo->title }}</h3>
                            @if($photo->caption)
                                <p class="text-xs text-gray-200 hidden sm:block">{{ Str::limit($photo->caption, 50) }}</p>
                            @endif
                        </div>
                        <!-- Overlay icon -->
                        <div class="absolute top-2 sm:top-4 right-2 sm:right-4 w-6 sm:w-8 h-6 sm:h-8 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            <svg class="w-3 sm:w-4 h-3 sm:h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="text-center mt-12">
                <a href="{{ route('public.gallery') }}" class="inline-flex items-center justify-center px-8 py-4 bg-white border border-slate-200 text-slate-700 font-semibold rounded-xl shadow-sm hover:shadow-lg hover:text-amber-600 transition-all duration-300 transform hover:scale-105">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Lihat Semua Foto
                </a>
            </div>
        </div>
    @endif

    <!-- Call to Action Section -->
    <div class="relative bg-white text-slate-900 overflow-hidden border-t border-slate-100">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-5">
            <svg class="w-full h-full" viewBox="0 0 100 100" fill="none">
                <defs>
                    <pattern id="cta-grid" width="20" height="20" patternUnits="userSpaceOnUse">
                        <path d="M 20 0 L 0 0 0 20" fill="none" stroke="currentColor" stroke-width="0.5"/>
                    </pattern>
                </defs>
                <rect width="100" height="100" fill="url(#cta-grid)" />
            </svg>
        </div>
        

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20 text-center">
            <div class="max-w-2xl sm:max-w-3xl lg:max-w-4xl mx-auto">
                <h2 class="text-3xl sm:text-4xl md:text-5xl font-bold font-heading mb-4 sm:mb-6 text-slate-900">Bergabunglah Bersama Kami</h2>
                <p class="text-lg sm:text-xl md:text-2xl text-slate-600 mb-8 sm:mb-10 lg:mb-12 leading-relaxed px-4">
                    Daftarkan diri Anda atau keluarga untuk mendapatkan pendidikan berkualitas dan terjangkau di PKBM kami. 
                    Mari wujudkan impian pendidikan yang lebih baik bersama-sama.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 sm:gap-6 justify-center items-center px-4">
                    <a href="{{ route('public.register') }}" class="group inline-flex items-center justify-center px-8 sm:px-10 py-4 sm:py-5 bg-amber-500 text-white font-bold rounded-xl shadow-xl hover:shadow-2xl hover:shadow-amber-500/30 transition-all duration-300 transform hover:scale-105 hover:bg-amber-600 w-full sm:w-auto">
                        <svg class="w-5 sm:w-6 h-5 sm:h-6 mr-2 sm:mr-3 group-hover:translate-x-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                        Daftar Sekarang
                    </a>
                    <a href="{{ route('public.about') }}" class="group inline-flex items-center justify-center px-8 sm:px-10 py-4 sm:py-5 border-2 border-slate-200 text-slate-600 font-bold rounded-xl hover:bg-slate-50 hover:text-slate-900 hover:border-slate-300 transition-all duration-300 transform hover:scale-105 w-full sm:w-auto">
                        <svg class="w-5 sm:w-6 h-5 sm:h-6 mr-2 sm:mr-3 group-hover:translate-x-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Tentang Kami
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>
