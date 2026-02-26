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

<div class="p-6">
    <x-header title="Galeri Foto" subtitle="Kelola foto-foto sekolah" separator>
        <x-slot:actions>
             <x-button label="Upload Foto Baru" icon="o-plus" class="btn-primary" @click="$refs.uploadForm.scrollIntoView({behavior: 'smooth'})" />
        </x-slot:actions>
    </x-header>

    @if (session()->has('message'))
        <x-alert title="Sukses" icon="o-check-circle" class="alert-success mb-6">
            {{ session('message') }}
        </x-alert>
    @endif

    {{-- Upload Form --}}
    <x-card id="upload-form" x-ref="uploadForm" title="Upload Foto Baru" subtitle="Pilih satu atau beberapa foto untuk ditambahkan ke galeri" separator shadow class="mb-8 border border-base-200">
        <form wire:submit="uploadPhotos" class="space-y-6">
            <x-file 
                wire:model="photos" 
                label="Pilih Foto" 
                accept="image/jpeg,image/jpg,image/png,image/webp"
                multiple
                required
            >
                <span class="text-xs opacity-70">Maksimal 5MB per file. Format: JPEG, PNG, WebP. Anda dapat memilih beberapa file sekaligus.</span>
                @if (count($photos) > 0)
                     <div class="text-sm font-medium mt-2">
                        {{ count($photos) }} file dipilih
                    </div>
                @endif
            </x-file>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-input 
                    wire:model="category" 
                    label="Kategori" 
                    type="text" 
                    required 
                    placeholder="Contoh: Kegiatan, Fasilitas, Acara"
                    list="category-suggestions"
                />
                <datalist id="category-suggestions">
                    @foreach ($availableCategories as $cat)
                        <option value="{{ $cat }}">
                    @endforeach
                </datalist>

                <x-input 
                    wire:model="caption" 
                    label="Keterangan (Opsional)" 
                    type="text" 
                    placeholder="Deskripsi singkat foto"
                />
            </div>

            <x-slot:actions>
                <x-button 
                    label="Upload Foto"
                    class="btn-primary" 
                    type="submit" 
                    spinner="uploadPhotos"
                />
            </x-slot:actions>
        </form>
    </x-card>

    {{-- Filter & Layout Controls --}}
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <span class="font-bold whitespace-nowrap">Filter Kategori:</span>
            <x-select 
                wire:model.live="filterCategory" 
                class="w-full sm:w-64"
                :options="collect([['id' => 'all', 'name' => 'Semua Kategori']])->merge(collect($availableCategories)->map(fn($c) => ['id' => $c, 'name' => $c]))"
                option-label="name"
                option-value="id"
            />
        </div>
    </div>

    {{-- Photo Grid --}}
    @if ($galleryPhotos->isEmpty())
        <x-card class="p-12 text-center" shadow>
            <x-icon name="o-photo" class="size-16 mb-4 opacity-10" />
            <p class="text-base-content/50">
                Belum ada foto di galeri. Upload foto pertama Anda!
            </p>
        </x-card>
    @else
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($galleryPhotos as $index => $photo)
                <x-card wire:key="photo-{{ $photo->id }}" class="group overflow-hidden border border-base-200 hover:shadow-lg transition-all" no-separator padding="p-0">
                    {{-- Photo Container --}}
                    <div class="aspect-square relative overflow-hidden bg-base-200">
                        <img 
                            src="{{ Storage::url($photo->thumbnail_path) }}" 
                            alt="{{ $photo->caption ?? 'Gallery Photo' }}"
                            class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                        >
                        
                        {{-- Quick Badges Overlay --}}
                        <div class="absolute top-2 left-2 flex flex-col gap-1">
                            <x-badge :label="$photo->category" class="badge-neutral badge-sm shadow-sm" />
                            @if ($photo->is_published)
                                <x-badge label="Live" class="badge-success badge-xs shadow-sm" />
                            @else
                                <x-badge label="Draft" class="badge-ghost badge-xs shadow-sm backdrop-blur-md" />
                            @endif
                        </div>

                        {{-- Order Helper Overlay --}}
                        <div class="absolute top-2 right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <x-button 
                                wire:click="moveUp({{ $photo->id }})" 
                                icon="o-chevron-left"
                                class="btn-xs btn-circle btn-neutral shadow-lg"
                                tooltip="Pindah ke kiri"
                            />
                            <x-button 
                                wire:click="moveDown({{ $photo->id }})" 
                                icon="o-chevron-right"
                                class="btn-xs btn-circle btn-neutral shadow-lg"
                                tooltip="Pindah ke kanan"
                            />
                        </div>
                    </div>

                    {{-- Info & Actions --}}
                    <div class="p-4 bg-base-100">
                        @if ($editingPhotoId === $photo->id)
                            {{-- Edit Mode --}}
                            <div class="space-y-4">
                                <x-input 
                                    wire:model="editCategory" 
                                    label="Kategori" 
                                    size="sm"
                                />
                                <x-input 
                                    wire:model="editCaption" 
                                    label="Keterangan" 
                                    size="sm"
                                />
                                <div class="flex gap-2">
                                    <x-button label="Simpan" wire:click="saveEdit" class="btn-primary btn-sm flex-1" spinner="saveEdit" />
                                    <x-button label="Batal" wire:click="cancelEdit" class="btn-ghost btn-sm flex-1" />
                                </div>
                            </div>
                        @else
                            {{-- View Mode --}}
                            <div class="mb-4 min-h-[3rem]">
                                @if ($photo->caption)
                                    <p class="text-sm font-medium line-clamp-2 leading-tight">{{ $photo->caption }}</p>
                                @else
                                    <p class="text-sm italic opacity-40">Tanpa keterangan</p>
                                @endif
                            </div>

                            {{-- Action Buttons --}}
                            <div class="flex items-center justify-between border-t border-base-200 pt-3">
                                <div class="flex gap-1">
                                    <x-button 
                                        wire:click="startEdit({{ $photo->id }})" 
                                        icon="o-pencil"
                                        class="btn-xs btn-ghost btn-square"
                                        tooltip="Edit"
                                    />
                                    <x-button 
                                        wire:click="togglePublish({{ $photo->id }})" 
                                        icon="{{ $photo->is_published ? 'o-eye-slash' : 'o-eye' }}"
                                        class="btn-xs btn-ghost btn-square"
                                        tooltip="{{ $photo->is_published ? 'Tarik dari publikasi' : 'Publikasikan' }}"
                                    />
                                </div>
                                
                                <x-button 
                                    wire:click="deletePhoto({{ $photo->id }})" 
                                    icon="o-trash"
                                    class="btn-xs btn-ghost btn-square text-error"
                                    wire:confirm="Yakin ingin menghapus foto ini? Semua versi gambar akan ikut terhapus."
                                    tooltip="Hapus Permanen"
                                />
                            </div>
                        @endif
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif
</div>
