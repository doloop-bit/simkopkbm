<?php

use Livewire\Volt\Component;
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

        return [
            'photos' => $query->get(),
            'title' => 'Galeri - ' . config('app.name'),
            'description' => 'Lihat koleksi foto kegiatan dan momen berharga di ' . config('app.name') . ' - Pusat Kegiatan Belajar Masyarakat.',
            'keywords' => 'Galeri, Foto, Kegiatan, Dokumentasi, PKBM, Galeri Foto',
            'ogTitle' => 'Galeri - ' . config('app.name'),
            'ogDescription' => 'Lihat koleksi foto kegiatan dan momen berharga di ' . config('app.name') . ' - Pusat Kegiatan Belajar Masyarakat.',
        ];
    }
}; ?>

<div>
    <!-- Page Header -->
    <div class="relative bg-slate-900 text-white overflow-hidden">
        <!-- Background Pattern -->
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
                @foreach($photos as $photo)
                    <div class="group relative overflow-hidden rounded-lg bg-gray-200 aspect-square shadow-sm hover:shadow-xl transition-all duration-300">
                        <x-global.optimized-image
                            :src="Storage::url($photo->thumbnail_path)"
                            :webp-src="$photo->thumbnail_webp_path ? Storage::url($photo->thumbnail_webp_path) : null"
                            :alt="$photo->title"
                            class="w-full h-full object-cover group-hover:scale-110 transition duration-500 cursor-pointer"
                            onclick="openLightbox('{{ $photo->web_path ? Storage::url($photo->web_path) : Storage::url($photo->original_path) }}', '{{ addslashes($photo->title) }}', '{{ addslashes($photo->caption ?? '') }}')"
                            :lazy="true"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition duration-300 flex items-end justify-center">
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

    <!-- Lightbox Modal -->
    <div id="lightbox" class="fixed inset-0 bg-black bg-opacity-90 z-50 hidden flex items-center justify-center p-4">
        <div class="relative max-w-4xl max-h-full">
            <!-- Close Button -->
            <button 
                onclick="closeLightbox()"
                class="absolute top-4 right-4 text-white hover:text-gray-300 z-10"
            >
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Image -->
            <img id="lightbox-image" src="" alt="" class="max-w-full max-h-full object-contain">
            
            <!-- Caption -->
            <div id="lightbox-caption" class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-75 text-white p-4">
                <h3 id="lightbox-title" class="font-semibold text-lg"></h3>
                <p id="lightbox-description" class="text-sm text-gray-300 mt-1"></p>
            </div>
        </div>
    </div>

    <!-- JavaScript for Lightbox -->
    <script>
        function openLightbox(imageSrc, title, description) {
            const lightbox = document.getElementById('lightbox');
            const image = document.getElementById('lightbox-image');
            const titleEl = document.getElementById('lightbox-title');
            const descriptionEl = document.getElementById('lightbox-description');
            
            image.src = imageSrc;
            image.alt = title;
            titleEl.textContent = title;
            descriptionEl.textContent = description;
            
            lightbox.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            const lightbox = document.getElementById('lightbox');
            lightbox.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Close lightbox on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeLightbox();
            }
        });

        // Close lightbox on background click
        document.getElementById('lightbox').addEventListener('click', function(e) {
            if (e.target === this) {
                closeLightbox();
            }
        });
    </script>
</div>