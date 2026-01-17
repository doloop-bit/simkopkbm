<?php

use App\Models\GalleryPhoto;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component
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

        foreach ($this->photos as $photo) {
            $filename = uniqid().'.'.$photo->extension();

            // Store original
            $originalPath = $photo->storeAs('gallery/original', $filename, 'public');

            // Generate thumbnail (300x300)
            $thumbnail = Image::read($photo->getRealPath())
                ->cover(300, 300);
            $thumbnailPath = 'gallery/thumbnails/'.$filename;
            Storage::disk('public')->put($thumbnailPath, $thumbnail->encode());

            // Generate web version (max 1200px width)
            $web = Image::read($photo->getRealPath())
                ->scaleDown(width: 1200);
            $webPath = 'gallery/web/'.$filename;
            Storage::disk('public')->put($webPath, $web->encode());

            // Get the next order value
            $maxOrder = GalleryPhoto::max('order') ?? 0;

            GalleryPhoto::create([
                'title' => $this->caption ?: 'Photo',
                'caption' => $this->caption,
                'category' => $this->category,
                'original_path' => $originalPath,
                'thumbnail_path' => $thumbnailPath,
                'web_path' => $webPath,
                'order' => $maxOrder + 1,
                'is_published' => true,
            ]);
        }

        $this->reset(['photos', 'category', 'caption']);
        $this->loadCategories();
        session()->flash('message', 'Foto berhasil diunggah.');
    }

    public function deletePhoto(int $id): void
    {
        $photo = GalleryPhoto::findOrFail($id);

        // Delete all image versions
        Storage::disk('public')->delete([
            $photo->original_path,
            $photo->thumbnail_path,
            $photo->web_path,
        ]);

        $photo->delete();

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

<div>
    <div class="mb-6">
        <flux:heading size="xl">Galeri Foto</flux:heading>
        <flux:subheading>Kelola foto-foto sekolah</flux:subheading>
    </div>

    @if (session()->has('message'))
        <flux:callout color="green" icon="check-circle" class="mb-6">
            {{ session('message') }}
        </flux:callout>
    @endif

    {{-- Upload Form --}}
    <div class="mb-8 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
        <flux:heading size="lg" class="mb-4">Upload Foto Baru</flux:heading>

        <form wire:submit="uploadPhotos" class="space-y-4">
            <div>
                <flux:input 
                    wire:model="photos" 
                    label="Pilih Foto" 
                    type="file" 
                    accept="image/jpeg,image/jpg,image/png,image/webp"
                    multiple
                    required
                />
                <flux:text class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    Maksimal 5MB per file. Format: JPEG, PNG, WebP. Anda dapat memilih beberapa file sekaligus.
                </flux:text>
                @if (count($photos) > 0)
                    <flux:text class="mt-2 text-sm font-medium">
                        {{ count($photos) }} file dipilih
                    </flux:text>
                @endif
            </div>

            <div wire:loading wire:target="photos" class="text-sm text-zinc-600 dark:text-zinc-400">
                Memuat file...
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <flux:input 
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

                <flux:input 
                    wire:model="caption" 
                    label="Keterangan (Opsional)" 
                    type="text" 
                    placeholder="Deskripsi singkat foto"
                />
            </div>

            <div class="flex justify-end">
                <flux:button 
                    variant="primary" 
                    type="submit" 
                    wire:loading.attr="disabled"
                    wire:target="uploadPhotos,photos"
                >
                    <span wire:loading.remove wire:target="uploadPhotos">Upload Foto</span>
                    <span wire:loading wire:target="uploadPhotos">Mengunggah...</span>
                </flux:button>
            </div>
        </form>
    </div>

    {{-- Filter --}}
    <div class="mb-6 flex items-center gap-4">
        <flux:text class="font-medium">Filter Kategori:</flux:text>
        <flux:select wire:model.live="filterCategory" class="w-64">
            <option value="all">Semua Kategori</option>
            @foreach ($availableCategories as $cat)
                <option value="{{ $cat }}">{{ $cat }}</option>
            @endforeach
        </flux:select>
    </div>

    {{-- Photo Grid --}}
    @if ($galleryPhotos->isEmpty())
        <div class="rounded-lg border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text class="text-zinc-500 dark:text-zinc-400">
                Belum ada foto di galeri. Upload foto pertama Anda!
            </flux:text>
        </div>
    @else
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($galleryPhotos as $photo)
                <div class="group relative overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                    {{-- Photo --}}
                    <div class="aspect-square overflow-hidden bg-zinc-100 dark:bg-zinc-900">
                        <img 
                            src="{{ Storage::url($photo->thumbnail_path) }}" 
                            alt="{{ $photo->caption ?? 'Gallery Photo' }}"
                            class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-110"
                        >
                    </div>

                    {{-- Info & Actions --}}
                    <div class="p-4">
                        @if ($editingPhotoId === $photo->id)
                            {{-- Edit Mode --}}
                            <div class="space-y-3">
                                <flux:input 
                                    wire:model="editCategory" 
                                    label="Kategori" 
                                    type="text" 
                                    size="sm"
                                />
                                <flux:input 
                                    wire:model="editCaption" 
                                    label="Keterangan" 
                                    type="text" 
                                    size="sm"
                                />
                                <div class="flex gap-2">
                                    <flux:button 
                                        wire:click="saveEdit" 
                                        variant="primary" 
                                        size="sm"
                                        class="flex-1"
                                    >
                                        Simpan
                                    </flux:button>
                                    <flux:button 
                                        wire:click="cancelEdit" 
                                        variant="ghost" 
                                        size="sm"
                                        class="flex-1"
                                    >
                                        Batal
                                    </flux:button>
                                </div>
                            </div>
                        @else
                            {{-- View Mode --}}
                            <div class="mb-3 min-h-[4rem]">
                                <flux:badge color="zinc" size="sm" class="mb-2">
                                    {{ $photo->category }}
                                </flux:badge>
                                @if ($photo->caption)
                                    <flux:text class="text-sm">{{ $photo->caption }}</flux:text>
                                @else
                                    <flux:text class="text-sm italic text-zinc-400">Tanpa keterangan</flux:text>
                                @endif
                            </div>

                            {{-- Status Badge --}}
                            <div class="mb-3">
                                @if ($photo->is_published)
                                    <flux:badge color="green" size="sm">Dipublikasikan</flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">Draft</flux:badge>
                                @endif
                            </div>

                            {{-- Action Buttons --}}
                            <div class="flex flex-wrap gap-2">
                                {{-- Reorder Buttons --}}
                                <div class="flex gap-1">
                                    <flux:button 
                                        wire:click="moveUp({{ $photo->id }})" 
                                        variant="ghost" 
                                        size="sm"
                                        title="Pindah ke atas"
                                    >
                                        ↑
                                    </flux:button>
                                    <flux:button 
                                        wire:click="moveDown({{ $photo->id }})" 
                                        variant="ghost" 
                                        size="sm"
                                        title="Pindah ke bawah"
                                    >
                                        ↓
                                    </flux:button>
                                </div>

                                {{-- Edit Button --}}
                                <flux:button 
                                    wire:click="startEdit({{ $photo->id }})" 
                                    variant="ghost" 
                                    size="sm"
                                >
                                    Edit
                                </flux:button>

                                {{-- Toggle Publish --}}
                                <flux:button 
                                    wire:click="togglePublish({{ $photo->id }})" 
                                    variant="ghost" 
                                    size="sm"
                                >
                                    {{ $photo->is_published ? 'Sembunyikan' : 'Publikasikan' }}
                                </flux:button>

                                {{-- Delete Button --}}
                                <flux:button 
                                    wire:click="deletePhoto({{ $photo->id }})" 
                                    variant="danger" 
                                    size="sm"
                                    wire:confirm="Apakah Anda yakin ingin menghapus foto ini? Semua versi gambar akan dihapus."
                                >
                                    Hapus
                                </flux:button>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
