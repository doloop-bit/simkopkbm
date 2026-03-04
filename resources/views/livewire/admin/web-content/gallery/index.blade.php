<?php

use App\Models\GalleryPhoto;
use App\Services\{ImageOptimizationService, CacheService};
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('components.admin.layouts.app')] class extends Component
{
    use WithFileUploads;

    public array $photos = [];

    public string $category = '';

    public string $caption = '';

    public string $filterCategory = 'all';

    public array $availableCategories = [];

    public ?int $editingPhotoId = null;

    public string $editCaption = '';

    public string $editCategory = '';

    public function mount(): void
    {
        $this->loadCategories();
    }

    public function rules(): array
    {
        return [
            'photos.*' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
            'category' => 'required|string|max:100',
            'caption' => 'nullable|string|max:255',
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'photos.*' => 'foto',
            'category' => 'kategori',
            'caption' => 'keterangan',
        ];
    }

    public function uploadPhotos(): void
    {
        $this->validate();

        $imageService = app(ImageOptimizationService::class);

        foreach ($this->photos as $photo) {
            // Process image with optimization
            $imagePaths = $imageService->processImage($photo, 'gallery', [
                'original' => null,
                'large' => 1200,
                'medium' => 800,
                'small' => 400,
                'thumbnail' => 300,
            ]);

            // Get the next order value
            $maxOrder = GalleryPhoto::max('order') ?? 0;

            GalleryPhoto::create([
                'title' => $this->caption ?: 'Photo',
                'caption' => $this->caption,
                'category' => $this->category,
                'original_path' => $imagePaths['original'],
                'thumbnail_path' => $imagePaths['thumbnail'],
                'web_path' => $imagePaths['large'],
                'medium_path' => $imagePaths['medium'] ?? null,
                'small_path' => $imagePaths['small'] ?? null,
                'original_webp_path' => $imagePaths['original_webp'] ?? null,
                'thumbnail_webp_path' => $imagePaths['thumbnail_webp'] ?? null,
                'web_webp_path' => $imagePaths['large_webp'] ?? null,
                'medium_webp_path' => $imagePaths['medium_webp'] ?? null,
                'small_webp_path' => $imagePaths['small_webp'] ?? null,
                'order' => $maxOrder + 1,
                'is_published' => true,
            ]);
        }

        // Clear gallery cache after upload
        $cacheService = app(CacheService::class);
        $cacheService->clearGalleryCache();

        $this->reset(['photos', 'category', 'caption']);
        $this->loadCategories();
        session()->flash('message', 'Foto berhasil diunggah.');
    }

    public function deletePhoto(int $id): void
    {
        $photo = GalleryPhoto::findOrFail($id);

        $imageService = app(ImageOptimizationService::class);
        
        // Collect all image paths
        $imagePaths = array_filter([
            $photo->original_path,
            $photo->thumbnail_path,
            $photo->web_path,
            $photo->medium_path,
            $photo->small_path,
            $photo->original_webp_path,
            $photo->thumbnail_webp_path,
            $photo->web_webp_path,
            $photo->medium_webp_path,
            $photo->small_webp_path,
        ]);

        // Delete all image versions
        $imageService->deleteImageVersions($imagePaths);

        $photo->delete();

        // Clear gallery cache after deletion
        $cacheService = app(CacheService::class);
        $cacheService->clearGalleryCache();

        $this->loadCategories();
        session()->flash('message', 'Foto berhasil dihapus.');
    }

    public function startEdit(int $id): void
    {
        $photo = GalleryPhoto::findOrFail($id);
        $this->editingPhotoId = $id;
        $this->editCaption = $photo->caption ?? '';
        $this->editCategory = $photo->category;
    }

    public function cancelEdit(): void
    {
        $this->editingPhotoId = null;
        $this->editCaption = '';
        $this->editCategory = '';
    }

    public function saveEdit(): void
    {
        $this->validate([
            'editCaption' => 'nullable|string|max:255',
            'editCategory' => 'required|string|max:100',
        ], [], [
            'editCaption' => 'keterangan',
            'editCategory' => 'kategori',
        ]);

        $photo = GalleryPhoto::findOrFail($this->editingPhotoId);
        $photo->update([
            'caption' => $this->editCaption,
            'category' => $this->editCategory,
            'title' => $this->editCaption ?: 'Photo',
        ]);

        $this->cancelEdit();
        $this->loadCategories();
        session()->flash('message', 'Foto berhasil diperbarui.');
    }

    public function togglePublish(int $id): void
    {
        $photo = GalleryPhoto::findOrFail($id);
        $photo->is_published = ! $photo->is_published;
        $photo->save();

        session()->flash('message', 'Status publikasi foto berhasil diubah.');
    }

    public function moveUp(int $id): void
    {
        $photo = GalleryPhoto::findOrFail($id);
        $previousPhoto = GalleryPhoto::where('order', '<', $photo->order)
            ->orderBy('order', 'desc')
            ->first();

        if ($previousPhoto) {
            $tempOrder = $photo->order;
            $photo->order = $previousPhoto->order;
            $previousPhoto->order = $tempOrder;

            $photo->save();
            $previousPhoto->save();
        }
    }

    public function moveDown(int $id): void
    {
        $photo = GalleryPhoto::findOrFail($id);
        $nextPhoto = GalleryPhoto::where('order', '>', $photo->order)
            ->orderBy('order', 'asc')
            ->first();

        if ($nextPhoto) {
            $tempOrder = $photo->order;
            $photo->order = $nextPhoto->order;
            $nextPhoto->order = $tempOrder;

            $photo->save();
            $nextPhoto->save();
        }
    }

    private function loadCategories(): void
    {
        $this->availableCategories = GalleryPhoto::distinct()
            ->pluck('category')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    public function with(): array
    {
        $query = GalleryPhoto::ordered();

        if ($this->filterCategory !== 'all') {
            $query->byCategory($this->filterCategory);
        }

        return [
            'galleryPhotos' => $query->get(),
        ];
    }
}; ?>

<div class="p-6 space-y-8 text-slate-900 dark:text-white pb-24 md:pb-6">
    @if (session()->has('message'))
        <x-ui.alert :title="__('Sukses')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100" dismissible>
            {{ session('message') }}
        </x-ui.alert>
    @endif

    <x-ui.header :title="__('Galeri Dokumentasi')" :subtitle="__('Kelola archive visual kegiatan, fasilitas, dan momen berharga sekolah.')" separator>
        <x-slot:actions>
             <x-ui.button :label="__('Unggah Foto')" icon="o-plus" class="btn-primary shadow-lg shadow-primary/20" @click="$refs.uploadForm.scrollIntoView({behavior: 'smooth'})" />
        </x-slot:actions>
    </x-ui.header>

    {{-- Upload Form --}}
    <x-ui.card id="upload-form" x-ref="uploadForm" shadow padding="false" class="border-none ring-1 ring-slate-100 dark:ring-slate-800">
        <div class="p-6 border-b border-slate-50 dark:border-slate-800/50 bg-slate-50/50 dark:bg-slate-900/50">
            <h3 class="font-bold text-slate-800 dark:text-white uppercase tracking-wider text-xs">{{ __('Batch Upload & Registrasi Foto') }}</h3>
        </div>
        <div class="p-8">
            <form wire:submit="uploadPhotos" class="space-y-8">
            <div class="max-w-md space-y-4">
                <x-ui.file 
                    wire:model="photos" 
                    :label="__('Pilih File Gambar')" 
                    accept="image/jpeg,image/jpg,image/png,image/webp"
                    multiple
                    required
                >
                    @if (count($photos) > 0)
                        <div class="text-xs font-semibold text-primary mt-2 px-1">
                            {{ count($photos) }} {{ __('file siap diunggah') }}
                        </div>
                    @endif
                </x-ui.file>
                <p class="text-xs text-slate-400 px-1 leading-relaxed">
                    * {{ __('Format: JPEG, PNG, WebP (Max 5MB/file). Anda dapat memilih beberapa foto sekaligus untuk unggah massal.') }}
                </p>
            </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <x-ui.input 
                            wire:model="category" 
                            :label="__('Kategori / Album')" 
                            type="text" 
                            required 
                            :placeholder="__('Contoh: Kegiatan, Fasilitas, Acara')"
                            list="category-suggestions"
                            class="font-medium"
                        />
                        <datalist id="category-suggestions">
                            @foreach ($availableCategories as $cat)
                                <option value="{{ $cat }}">
                            @endforeach
                        </datalist>
                    </div>

                    <x-ui.input 
                        wire:model="caption" 
                        :label="__('Keterangan Bersama (Opsional)')" 
                        type="text" 
                        :placeholder="__('Deskripsi singkat untuk semua foto yang diunggah...')"
                        class="text-sm"
                    />
                </div>

                <div class="flex items-center justify-end pt-6 border-t border-slate-50 dark:border-slate-800">
                    <x-ui.button 
                        :label="__('Mulai Proses Unggah')"
                        class="btn-primary shadow-xl shadow-primary/20 px-8" 
                        type="submit" 
                        spinner="uploadPhotos"
                    />
                </div>
            </form>
        </div>
    </x-ui.card>

    {{-- Filter & Layout Controls --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 bg-white dark:bg-slate-900 p-4 rounded-3xl ring-1 ring-slate-100 dark:ring-slate-800">
        <div class="flex items-center gap-4 px-2">
            <x-ui.icon name="o-funnel" class="size-4 text-slate-400" />
            <h4 class="font-bold text-slate-800 dark:text-white uppercase tracking-wider text-[10px] whitespace-nowrap">{{ __('Filter Koleksi') }}</h4>
            <div class="h-4 w-px bg-slate-100 dark:bg-slate-800"></div>
            <x-ui.select 
                wire:model.live="filterCategory" 
                class="min-w-64 border-none shadow-none bg-transparent font-semibold text-primary"
                :options="collect([['id' => 'all', 'name' => __('Tampilkan Semua Album')]])->merge(collect($availableCategories)->map(fn($c) => ['id' => $c, 'name' => $c]))"
            />
        </div>
        <x-ui.badge :label="$galleryPhotos->count() . ' ' . __('Foto Terdaftar')" class="bg-indigo-50 text-indigo-600 border-none font-bold text-[10px]" />
    </div>

    {{-- Photo Grid --}}
    @if ($galleryPhotos->isEmpty())
        <div class="flex flex-col items-center justify-center py-32 text-slate-300 dark:text-slate-700 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-[32px] bg-slate-50/50 dark:bg-slate-900/50 transition-all text-center px-6">
            <x-ui.icon name="o-photo" class="size-20 mb-6 opacity-20" />
            <p class="text-sm font-semibold uppercase tracking-widest">{{ __('Galeri Belum Berisi Dokumentasi') }}</p>
            <x-ui.button :label="__('Mulai Mengisi Galeri')" @click="$refs.uploadForm.scrollIntoView({behavior: 'smooth'})" class="mt-8 btn-ghost text-primary btn-sm font-bold" />
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach ($galleryPhotos as $index => $photo)
                <x-ui.card wire:key="photo-{{ $photo->id }}" shadow padding="false" class="group relative overflow-hidden border-none ring-1 ring-slate-100 dark:ring-slate-800 hover:ring-primary/20 transition-all duration-500">
                    {{-- Photo Frame --}}
                    <div class="aspect-square relative overflow-hidden bg-slate-100 dark:bg-slate-900">
                        <img 
                            src="{{ Storage::url($photo->thumbnail_path) }}" 
                            alt="{{ $photo->caption ?? 'Gallery Photo' }}"
                            class="absolute inset-0 size-full object-cover transition-transform duration-700 group-hover:scale-110"
                        >
                        
                        {{-- Overlays --}}
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

                        {{-- Metadata Overlay --}}
                        <div class="absolute top-4 left-4 flex flex-col gap-2">
                            <x-ui.badge :label="$photo->category" class="bg-black/40 backdrop-blur-md text-white border-white/20 font-bold text-[9px] px-3 py-1 uppercase tracking-wider" />
                            @if (!$photo->is_published)
                                <x-ui.badge :label="__('ARSIP/DRAFT')" class="bg-amber-500 text-white border-none font-bold text-[8px] px-2 py-0.5" />
                            @endif
                        </div>

                        {{-- Order Helper Overlay --}}
                        <div class="absolute top-4 right-4 flex flex-col gap-1 translate-x-12 opacity-0 group-hover:translate-x-0 group-hover:opacity-100 transition-all duration-300">
                            <x-ui.button 
                                wire:click="moveUp({{ $photo->id }})" 
                                icon="o-chevron-left"
                                class="size-8 min-h-0 p-0 bg-white/90 dark:bg-slate-800/90 hover:bg-white text-slate-600 dark:text-white rounded-xl shadow-sm border-none"
                            />
                            <x-ui.button 
                                wire:click="moveDown({{ $photo->id }})" 
                                icon="o-chevron-right"
                                class="size-8 min-h-0 p-0 bg-white/90 dark:bg-slate-800/90 hover:bg-white text-slate-600 dark:text-white rounded-xl shadow-sm border-none"
                            />
                        </div>

                        {{-- Floating Info Overlay --}}
                        <div class="absolute bottom-4 left-4 right-4 translate-y-4 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-300">
                            <p class="text-white text-[10px] font-semibold leading-tight uppercase tracking-wider line-clamp-2">{{ $photo->caption ?: __('Tidak Ada Keterangan') }}</p>
                        </div>
                    </div>

                    {{-- Management Control Panel --}}
                    <div class="p-4 bg-white dark:bg-slate-900 border-t border-slate-50 dark:border-slate-800/50">
                        @if ($editingPhotoId === $photo->id)
                            {{-- Inline Edit Experience --}}
                            <div class="space-y-4 animate-in fade-in slide-in-from-bottom-2 duration-300">
                                <x-ui.input 
                                    wire:model="editCategory" 
                                    :label="__('Album')" 
                                    class="text-xs font-semibold"
                                />
                                <x-ui.textarea 
                                    wire:model="editCaption" 
                                    :label="__('Deskripsi')" 
                                    rows="2"
                                    class="text-xs leading-relaxed"
                                />
                                <div class="grid grid-cols-2 gap-2 pt-2">
                                    <x-ui.button :label="__('Simpan')" wire:click="saveEdit" class="btn-primary btn-xs font-bold" spinner="saveEdit" />
                                    <x-ui.button :label="__('Batal')" wire:click="cancelEdit" class="btn-ghost btn-xs font-bold" />
                                </div>
                            </div>
                        @else
                            {{-- View Mode Actions --}}
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-1">
                                    <x-ui.button 
                                        wire:click="startEdit({{ $photo->id }})" 
                                        icon="o-pencil"
                                        class="btn-ghost btn-xs text-slate-400 hover:text-primary"
                                    />
                                    <x-ui.button 
                                        wire:click="togglePublish({{ $photo->id }})" 
                                        icon="{{ $photo->is_published ? 'o-eye-slash' : 'o-eye' }}"
                                        class="btn-ghost btn-xs text-slate-400 hover:text-indigo-500"
                                    />
                                </div>
                                
                                <x-ui.button 
                                    wire:click="deletePhoto({{ $photo->id }})" 
                                    icon="o-trash"
                                    class="btn-ghost btn-xs text-slate-300 hover:text-rose-500"
                                    wire:confirm="__('Hapus foto ini secara permanen dari server?') "
                                />
                            </div>
                        @endif
                    </div>
                </x-ui.card>
            @endforeach
        </div>
    @endif
</div>
