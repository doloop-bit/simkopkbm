<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Storage;
use App\Models\GalleryPhoto;
use App\Services\CacheService;

new #[Layout('components.public.layouts.public')] class extends Component {
    public $selectedCategory = '';
    public $categories = [];

    public function mount(): void
    {
        $cacheService = app(CacheService::class);
        $this->categories = $cacheService->getGalleryCategories();
    }

    public function filterByCategory($category = ''): void
    {
        $this->selectedCategory = $category;
    }

    public function with(): array
    {
        $query = GalleryPhoto::published()->ordered();
        
        if ($this->selectedCategory) {
            $query->where('category', $this->selectedCategory);
        }

        $photos = $query->get();

        // Prepare data for lightbox
        $lightboxImages = $photos->map(fn($photo) => [
            'src' => $photo->web_path ? Storage::url($photo->web_path) : Storage::url($photo->thumbnail_path),
            'title' => $photo->title,
            'caption' => $photo->caption,
            'category' => $photo->category,
        ])->values()->all();

        return [
            'photos' => $photos,
            'lightboxImages' => $lightboxImages,
            'title' => 'Galeri - ' . config('app.name'),
            'description' => 'Lihat koleksi foto kegiatan dan momen berharga di ' . config('app.name') . ' - Pusat Kegiatan Belajar Masyarakat.',
            'keywords' => 'Galeri, Foto, Kegiatan, Dokumentasi, PKBM, Galeri Foto',
            'ogTitle' => 'Galeri - ' . config('app.name'),
            'ogDescription' => 'Lihat koleksi foto kegiatan dan momen berharga di ' . config('app.name') . ' - Pusat Kegiatan Belajar Masyarakat.',
        ];
    }
}; ?>

<div x-data="{
    lightboxOpen: false,
    activeImageIndex: 0,
    images: @js($lightboxImages),
    
    openLightbox(index) {
        this.activeImageIndex = index;
        this.lightboxOpen = true;
        document.body.style.overflow = 'hidden';
    },
    
    closeLightbox() {
        this.lightboxOpen = false;
        document.body.style.overflow = 'auto';
    },
    
    next() {
        this.activeImageIndex = (this.activeImageIndex + 1) % this.images.length;
    },
    
    prev() {
        this.activeImageIndex = (this.activeImageIndex - 1 + this.images.length) % this.images.length;
    },

    getActiveImage() {
        return this.images[this.activeImageIndex] || {};
    }
}"
@keydown.escape.window="closeLightbox()"
@keydown.arrow-right.window="next()"
@keydown.arrow-left.window="prev()">
    
    <!-- Page Header -->
    <div class="relative bg-slate-900 text-white overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 100 100" fill="none">
                <defs>
                    <pattern id="gallery-grid" width="20" height="20" patternUnits="userSpaceOnUse">
                        <path d="M 20 0 L 0 0 0 20" fill="none" stroke="currentColor" stroke-width="0.5"/>
                    </pattern>
                </defs>
                <rect width="100" height="100" fill="url(#gallery-grid)" />
            </svg>
        </div>
        
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20">
            <div class="text-center">
                <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold font-heading mb-4">Galeri Foto</h1>
                <p class="text-lg sm:text-xl md:text-2xl text-slate-300">
                    Dokumentasi kegiatan dan fasilitas PKBM
                </p>
                <div class="w-24 h-1 bg-amber-500 mx-auto mt-6 rounded-full"></div>
            </div>
        </div>
    </div>

    <!-- Gallery Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        <!-- Category Filter -->
        @if(count($categories) > 0)
            <div class="mb-6 sm:mb-8">
                <div class="flex flex-wrap gap-2 justify-center">
                    <button 
                        wire:click="filterByCategory('')"
                        class="px-4 sm:px-6 py-2 sm:py-3 rounded-full text-sm font-semibold transition-all duration-200 {{ $selectedCategory === '' ? 'bg-amber-500 text-white shadow-lg' : 'bg-white text-slate-600 hover:bg-slate-50 hover:text-amber-600 shadow-md border border-slate-100' }}"
                    >
                        Semua
                    </button>
                    @foreach($categories as $category)
                        <button 
                            wire:click="filterByCategory('{{ $category }}')"
                            class="px-4 sm:px-6 py-2 sm:py-3 rounded-full text-sm font-semibold transition-all duration-200 {{ $selectedCategory === $category ? 'bg-amber-500 text-white shadow-lg' : 'bg-white text-slate-600 hover:bg-slate-50 hover:text-amber-600 shadow-md border border-slate-100' }}"
                        >
                            {{ $category }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Photo Grid -->
        @if($photos->count() > 0)
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 sm:gap-4 lg:gap-6">
                @foreach($photos as $index => $photo)
                    <div class="group relative overflow-hidden rounded-lg bg-gray-200 aspect-square shadow-sm hover:shadow-xl transition-all duration-300">
                        <x-global.optimized-image
                            :src="Storage::url($photo->thumbnail_path)"
                            :webp-src="$photo->thumbnail_webp_path ? Storage::url($photo->thumbnail_webp_path) : null"
                            :alt="$photo->title"
                            class="w-full h-full object-cover group-hover:scale-110 transition duration-500 cursor-pointer"
                            @click="openLightbox({{ $index }})"
                            :lazy="true"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition duration-300 flex items-end justify-center pointer-events-none">
                            <div class="text-white p-2 sm:p-4 text-center w-full transform translate-y-4 group-hover:translate-y-0 transition duration-300">
                                <h3 class="font-semibold text-xs sm:text-sm mb-1 font-heading">{{ $photo->title }}</h3>
                                @if($photo->caption)
                                    <p class="text-[10px] text-gray-200 hidden sm:block line-clamp-2">{{ Str::limit($photo->caption, 50) }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- Empty State -->
            <div class="text-center py-12 sm:py-16 bg-white rounded-2xl shadow-sm border border-slate-100">
                <div class="bg-slate-50 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="mt-4 text-lg font-bold text-slate-900 font-heading">Belum Ada Foto</h3>
                <p class="mt-2 text-slate-500">
                    @if($selectedCategory)
                        Tidak ada foto dalam kategori "{{ $selectedCategory }}".
                    @else
                        Galeri foto belum tersedia saat ini.
                    @endif
                </p>
                @if($selectedCategory)
                    <button 
                        wire:click="filterByCategory('')"
                        class="mt-6 inline-flex items-center px-6 py-2.5 border border-transparent text-sm font-bold rounded-full text-white bg-amber-500 hover:bg-amber-600 transition shadow-lg hover:shadow-amber-500/30"
                    >
                        Lihat Semua Foto
                    </button>
                @endif
            </div>
        @endif
    </div>

    <!-- Alpine Lightbox Modal -->
    <div 
        x-show="lightboxOpen" 
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/95 z-50 flex items-center justify-center backdrop-blur-sm"
        style="display: none;"
    >
        <!-- Close Button -->
        <button 
            @click="closeLightbox()"
            class="absolute top-4 right-4 text-white/70 hover:text-white z-50 p-2 rounded-full hover:bg-white/10 transition"
        >
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            <span class="sr-only">Close</span>
        </button>

        <!-- Navigation Buttons -->
        <!-- Prev -->
        <button 
            @click.stop="prev()"
            class="absolute left-4 top-1/2 -translate-y-1/2 text-white/70 hover:text-white z-50 p-3 rounded-full hover:bg-white/10 transition hidden md:block"
        >
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </button>
        <!-- Next -->
        <button 
            @click.stop="next()"
            class="absolute right-4 top-1/2 -translate-y-1/2 text-white/70 hover:text-white z-50 p-3 rounded-full hover:bg-white/10 transition hidden md:block"
        >
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </button>

        <!-- Image Container -->
        <div class="relative w-full h-full flex items-center justify-center p-4 md:p-12" @click.outside="closeLightbox()">
            <div class="relative max-h-full max-w-7xl flex flex-col items-center">
                <img 
                    :src="getActiveImage().src" 
                    :alt="getActiveImage().title"
                    class="max-w-full max-h-[85vh] object-contain shadow-2xl rounded-sm"
                    draggable="false"
                >
                
                <!-- Caption/Info -->
                <div class="mt-4 text-center text-white max-w-2xl px-4">
                    <h3 class="text-xl font-bold font-heading" x-text="getActiveImage().title"></h3>
                    <p class="text-white/80 mt-2 text-sm" x-text="getActiveImage().caption"></p>
                    <p class="text-white/40 mt-1 text-xs" x-show="getActiveImage().category">
                        Kategori: <span x-text="getActiveImage().category"></span>
                    </p>
                </div>

                <!-- Counter -->
                <div class="absolute top-0 left-0 bg-black/50 text-white text-xs px-2 py-1 rounded" x-text="(activeImageIndex + 1) + ' / ' + images.length"></div>
            </div>
        </div>
    </div>
</div>