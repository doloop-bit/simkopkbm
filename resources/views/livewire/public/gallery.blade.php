<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Storage;
use App\Models\GalleryPhoto;

new #[Layout('components.layouts.public')] class extends Component {
    public $selectedCategory = '';
    public $categories = [];

    public function mount(): void
    {
        $this->categories = GalleryPhoto::published()
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
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
    <div class="relative bg-gradient-to-br from-green-600 via-green-700 to-emerald-800 text-white overflow-hidden">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-20">
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
                <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold mb-4">Galeri Foto</h1>
                <p class="text-lg sm:text-xl md:text-2xl text-green-100">
                    Dokumentasi kegiatan dan fasilitas PKBM
                </p>
                <div class="w-24 h-1 bg-white/30 mx-auto mt-6 rounded-full"></div>
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
                        class="px-4 sm:px-6 py-2 sm:py-3 rounded-full text-sm font-semibold transition-all duration-200 {{ $selectedCategory === '' ? 'bg-gradient-to-r from-green-600 to-emerald-600 text-white shadow-lg' : 'bg-white text-gray-700 hover:bg-green-50 hover:text-green-700 shadow-md' }}"
                    >
                        Semua
                    </button>
                    @foreach($categories as $category)
                        <button 
                            wire:click="filterByCategory('{{ $category }}')"
                            class="px-4 sm:px-6 py-2 sm:py-3 rounded-full text-sm font-semibold transition-all duration-200 {{ $selectedCategory === $category ? 'bg-gradient-to-r from-green-600 to-emerald-600 text-white shadow-lg' : 'bg-white text-gray-700 hover:bg-green-50 hover:text-green-700 shadow-md' }}"
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
                    <div class="group relative overflow-hidden rounded-lg bg-gray-200 aspect-square">
                        <img 
                            src="{{ Storage::url($photo->thumbnail_path) }}" 
                            alt="{{ $photo->title }}"
                            class="w-full h-full object-cover group-hover:scale-110 transition duration-300 cursor-pointer"
                            onclick="openLightbox('{{ Storage::url($photo->image_path) }}', '{{ addslashes($photo->title) }}', '{{ addslashes($photo->description ?? '') }}')"
                            loading="lazy"
                        >
                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition duration-300 flex items-center justify-center">
                            <div class="text-white opacity-0 group-hover:opacity-100 transition duration-300 text-center p-2 sm:p-4">
                                <h3 class="font-semibold text-xs sm:text-sm mb-1">{{ $photo->title }}</h3>
                                @if($photo->description)
                                    <p class="text-xs text-gray-200 hidden sm:block">{{ Str::limit($photo->description, 50) }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- Empty State -->
            <div class="text-center py-12 sm:py-16">
                <svg class="mx-auto h-20 sm:h-24 w-20 sm:w-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">Belum Ada Foto</h3>
                <p class="mt-2 text-gray-500">
                    @if($selectedCategory)
                        Tidak ada foto dalam kategori "{{ $selectedCategory }}".
                    @else
                        Galeri foto belum tersedia saat ini.
                    @endif
                </p>
                @if($selectedCategory)
                    <button 
                        wire:click="filterByCategory('')"
                        class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-blue-600 bg-blue-100 hover:bg-blue-200 transition"
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