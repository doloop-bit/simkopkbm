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
    <div class="relative bg-zinc-950 text-zinc-50 overflow-hidden">
        <!-- Background Pattern -->
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-zinc-800/40 via-zinc-950 to-zinc-950"></div>
        <div class="absolute inset-0 opacity-[0.03] pointer-events-none" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
        
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-28 lg:py-32">
            <div class="text-center max-w-4xl mx-auto">
                @if($schoolProfile && $schoolProfile->logo_path)
                    <div class="mb-8">
                        <img src="{{ Storage::url($schoolProfile->logo_path) }}" alt="{{ $schoolProfile->name }}" class="h-20 sm:h-24 lg:h-28 mx-auto drop-shadow-xl hover:scale-105 transition-transform duration-300">
                    </div>
                @endif
                <h1 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-bold font-heading mb-6 tracking-tight leading-tight">
                    <span class="bg-gradient-to-br from-white via-zinc-200 to-zinc-400 bg-clip-text text-transparent">
                        {{ $schoolProfile?->name ?? config('app.name') }}
                    </span>
                </h1>
                <p class="text-lg sm:text-xl md:text-2xl mb-8 text-zinc-400 font-medium tracking-wide">
                    Pusat Kegiatan Belajar Masyarakat
                </p>
                @if($schoolProfile && $schoolProfile->vision)
                    <p class="text-base sm:text-lg text-zinc-400 leading-relaxed mb-10 px-4 max-w-3xl mx-auto font-light">
                        {{ Str::limit($schoolProfile->vision, 200) }}
                    </p>
                @endif
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center px-4">
                    <a href="{{ route('public.programs.index') }}" class="group inline-flex items-center justify-center px-8 py-4 bg-zinc-100 text-zinc-900 font-semibold rounded-full shadow-lg hover:shadow-xl hover:bg-white transition-all duration-300 transform hover:-translate-y-0.5 w-full sm:w-auto">
                        Lihat Program
                        <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </a>
                    <a href="{{ route('public.register') }}" class="group inline-flex items-center justify-center px-8 py-4 bg-amber-500 text-white font-semibold rounded-full shadow-lg shadow-amber-500/20 hover:shadow-amber-500/40 hover:bg-amber-400 transition-all duration-300 transform hover:-translate-y-0.5 w-full sm:w-auto">
                        Pendaftaran Siswa
                        <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
        
    <!-- Subtle gradient separator -->
        <div class="absolute bottom-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-zinc-700 to-transparent"></div>
    </div>

    <!-- Admission Banner Carousel Section -->
    @if($programs->count() > 0)
    <div x-data="{
            activeSlide: 0,
            slides: [
                @foreach($programs as $program)
                {
                    image: '{{ $program->image_path ? Storage::url($program->image_path) : 'https://placehold.co/1920x600/27272a/ffffff.png?text=Pendaftaran+'.urlencode($program->name) }}',
                    title: '{{ addslashes($program->name) }}',
                    desc: '{{ addslashes(Str::limit($program->description, 100)) }}'
                }{{ !$loop->last ? ',' : '' }}
                @endforeach
            ],
            init() {
                if (this.slides.length > 1) {
                    setInterval(() => {
                        this.activeSlide = this.activeSlide === this.slides.length - 1 ? 0 : this.activeSlide + 1;
                    }, 5000);
                }
            }
        }"
        class="relative w-full overflow-hidden bg-zinc-900 group border-b border-zinc-200/50"
    >
        <div class="relative w-full aspect-[16/9] sm:aspect-[21/9] lg:aspect-[3/1]">
            <template x-for="(slide, index) in slides" :key="index">
                <div x-show="activeSlide === index" 
                     x-transition:enter="transition-opacity duration-1000 ease-in-out" 
                     x-transition:enter-start="opacity-0" 
                     x-transition:enter-end="opacity-100" 
                     x-transition:leave="transition-opacity duration-1000 ease-in-out absolute inset-0" 
                     x-transition:leave-start="opacity-100" 
                     x-transition:leave-end="opacity-0" 
                     class="absolute inset-0 w-full h-full">
                    <img :src="slide.image" class="object-cover w-full h-full opacity-90" :alt="slide.title">
                    <div class="absolute inset-0 bg-gradient-to-t from-zinc-950/95 via-zinc-950/50 to-transparent"></div>
                    <div class="absolute bottom-0 left-0 w-full px-6 py-12 sm:p-16 text-center sm:text-left text-white">
                        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                            <span class="inline-block px-3 py-1 mb-4 text-xs font-semibold tracking-wider text-amber-500 uppercase bg-amber-500/10 rounded-full border border-amber-500/20 shadow-sm backdrop-blur-sm">Penerimaan Siswa Baru</span>
                            <h3 class="text-3xl sm:text-4xl lg:text-5xl font-bold font-heading mb-4 drop-shadow-lg tracking-tight" x-text="slide.title"></h3>
                            <p class="text-base sm:text-lg lg:text-xl text-zinc-100 drop-shadow-md max-w-2xl lg:max-w-3xl font-light leading-relaxed mb-8" x-text="slide.desc"></p>
                            
                            <a href="{{ route('public.register') }}" class="inline-flex items-center justify-center px-6 py-3 bg-amber-500 text-white font-semibold rounded-full hover:bg-amber-400 transition-colors shadow-lg shadow-amber-500/20">
                                Daftar Sekarang
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Navigation Buttons -->
        <button x-show="slides.length > 1" @click="activeSlide = activeSlide === 0 ? slides.length - 1 : activeSlide - 1" class="absolute left-2 sm:left-6 top-1/2 -translate-y-1/2 bg-black/40 hover:bg-amber-500 text-white p-2.5 sm:p-3 rounded-full opacity-0 group-hover:opacity-100 transition-all duration-300 backdrop-blur-md shadow-xl z-10 focus:outline-none">
            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
        </button>
        <button x-show="slides.length > 1" @click="activeSlide = activeSlide === slides.length - 1 ? 0 : activeSlide + 1" class="absolute right-2 sm:right-6 top-1/2 -translate-y-1/2 bg-black/40 hover:bg-amber-500 text-white p-2.5 sm:p-3 rounded-full opacity-0 group-hover:opacity-100 transition-all duration-300 backdrop-blur-md shadow-xl z-10 focus:outline-none">
            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        </button>

        <!-- Indicators -->
        <div x-show="slides.length > 1" class="absolute bottom-6 left-1/2 -translate-x-1/2 flex space-x-2 z-10">
            <template x-for="(slide, index) in slides" :key="index">
                <button @click="activeSlide = index" 
                        :class="{'w-8 bg-amber-500 hover:bg-amber-400': activeSlide === index, 'w-2 bg-white/40 hover:bg-white/80': activeSlide !== index}" 
                        class="h-2 rounded-full transition-all duration-300 focus:outline-none shadow-sm"></button>
            </template>
        </div>
    </div>
    @endif

    <!-- Latest News Section -->
    @if($latestNews->count() > 0)
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-6">
                <div class="max-w-2xl">
                    <h2 class="text-3xl md:text-4xl font-bold font-heading text-zinc-900 tracking-tight">Berita Terbaru</h2>
                    <p class="text-zinc-500 mt-4 text-lg">Ikuti perkembangan terbaru dan kegiatan menarik di PKBM kami.</p>
                </div>
                <a href="{{ route('public.news.index') }}" class="inline-flex items-center text-zinc-900 font-semibold hover:text-amber-600 transition-colors group">
                    Lihat Semua
                    <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($latestNews as $article)
                    <article class="group relative flex flex-col items-start justify-between bg-white rounded-2xl ring-1 ring-zinc-200/50 shadow-sm hover:shadow-lg hover:ring-zinc-200 transition-all duration-300 overflow-hidden">
                        <div class="block w-full overflow-hidden aspect-[16/9] bg-zinc-100">
                            @if($article->featured_image_path)
                                <img src="{{ Storage::url($article->featured_image_path) }}" alt="{{ $article->title }}" class="object-cover w-full h-full group-hover:scale-105 transition-transform duration-500">
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg class="w-12 h-12 text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <div class="p-6 md:p-8 flex-1 flex flex-col">
                            <div class="flex items-center gap-x-4 text-xs mb-4">
                                <time datetime="{{ $article->published_at->format('Y-m-d') }}" class="text-zinc-500">
                                    {{ $article->published_at->format('d M Y') }}
                                </time>
                            </div>
                            <h3 class="mt-3 text-xl font-bold font-heading leading-tight text-zinc-900 group-hover:text-amber-600 transition-colors line-clamp-2">
                                <a href="{{ route('public.news.show', $article->slug) }}">
                                    <span class="absolute inset-0"></span>
                                    {{ $article->title }}
                                </a>
                            </h3>
                            <p class="mt-4 text-zinc-600 line-clamp-3 text-sm leading-relaxed flex-1">
                                {{ $article->excerpt ?? Str::limit(strip_tags($article->content), 120) }}
                            </p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Programs Section -->
    @if($programs->count() > 0)
        <div class="bg-zinc-50 border-y border-zinc-200/60 py-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <h2 class="text-3xl md:text-4xl font-bold font-heading text-zinc-900 tracking-tight">Program Pendidikan</h2>
                    <p class="mt-4 text-lg text-zinc-500">
                        Kami menawarkan berbagai program pendidikan berkualitas yang disesuaikan dengan kebutuhan dan perkembangan masyarakat.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 justify-center">
                    @foreach($programs as $program)
                        <a href="{{ route('public.programs.show', $program->slug) }}" class="group flex flex-col items-center text-center bg-white rounded-3xl p-8 ring-1 ring-zinc-200/50 shadow-sm hover:shadow-xl hover:ring-zinc-200 transition-all duration-300 hover:-translate-y-1">
                            <div class="w-16 h-16 rounded-2xl bg-zinc-900 text-white flex items-center justify-center mb-6 shadow-md group-hover:bg-amber-500 transition-colors">
                                @if($program->image_path)
                                    <img src="{{ Storage::url($program->image_path) }}" alt="{{ $program->name }}" class="w-full h-full object-cover rounded-2xl">
                                @else
                                    <span class="text-3xl font-bold font-heading">{{ Str::upper(Str::substr($program->name, 0, 1)) }}</span>
                                @endif
                            </div>
                            <h3 class="text-xl font-bold text-zinc-900 mb-2 font-heading tracking-tight">
                                {{ $program->name }}
                            </h3>
                            <div class="inline-flex items-center rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-800 mb-4">
                                {{ $program->level?->name }}
                            </div>
                            <p class="text-zinc-500 text-sm line-clamp-3 leading-relaxed">
                                {{ Str::limit($program->description, 100) }}
                            </p>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Gallery Preview Section -->
    @if($featuredPhotos->count() > 0)
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-6">
                <div class="max-w-2xl">
                    <h2 class="text-3xl md:text-4xl font-bold font-heading text-zinc-900 tracking-tight">Galeri Foto</h2>
                    <p class="mt-4 text-lg text-zinc-500">
                        Dokumentasi kegiatan dan momen berharga dalam perjalanan pendidikan di PKBM kami.
                    </p>
                </div>
                <a href="{{ route('public.gallery') }}" class="inline-flex items-center text-zinc-900 font-semibold hover:text-amber-600 transition-colors group">
                    Lihat Semua
                    <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                @foreach($featuredPhotos as $photo)
                    <a href="{{ route('public.gallery') }}" class="group relative overflow-hidden rounded-2xl aspect-square ring-1 ring-zinc-200/50 shadow-sm hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 block">
                        <x-global.optimized-image
                            :src="Storage::url($photo->thumbnail_path)"
                            :webp-src="$photo->thumbnail_webp_path ? Storage::url($photo->thumbnail_webp_path) : null"
                            :alt="$photo->title"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                            :lazy="true"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-zinc-950/80 via-zinc-950/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        <div class="absolute bottom-0 left-0 right-0 p-4 text-white transform translate-y-4 group-hover:translate-y-0 transition-transform duration-300 opacity-0 group-hover:opacity-100">
                            <h3 class="font-bold text-sm leading-tight mb-1">{{ $photo->title }}</h3>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Call to Action Section -->
    <div class="relative bg-zinc-950 text-white overflow-hidden py-24 sm:py-32">
        <div class="absolute inset-0 overflow-hidden">
             <div class="absolute -top-1/2 -right-1/4 w-full h-full bg-gradient-to-b from-zinc-800/50 to-transparent rounded-full blur-3xl transform rotate-12 opacity-50"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="max-w-3xl mx-auto">
                <h2 class="text-3xl sm:text-4xl md:text-5xl font-bold font-heading mb-6 tracking-tight">Bergabunglah Bersama Kami</h2>
                <p class="text-lg sm:text-xl text-zinc-400 mb-10 leading-relaxed font-light">
                    Daftarkan diri Anda atau keluarga untuk mendapatkan pendidikan berkualitas dan terjangkau. 
                    Mari wujudkan masa depan yang lebih baik.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                    <a href="{{ route('public.register') }}" class="group inline-flex items-center justify-center px-8 py-4 bg-amber-500 text-white font-semibold rounded-full shadow-xl hover:shadow-amber-500/30 hover:bg-amber-400 transition-all duration-300 transform hover:-translate-y-0.5 w-full sm:w-auto">
                        Pendaftaran Siswa
                        <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </a>
                    <a href="{{ route('public.about') }}" class="group inline-flex items-center justify-center px-8 py-4 bg-zinc-800 text-white font-semibold rounded-full hover:bg-zinc-700 transition-all duration-300 w-full sm:w-auto">
                        Pelajari Lebih Lanjut
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>
