<?php

use App\Models\NewsArticle;
use App\Services\CacheService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public ?NewsArticle $article = null;

    public string $title = '';

    public string $content = '';

    public string $excerpt = '';

    public string $status = 'draft';

    public $featuredImage;

    public ?string $currentFeaturedImagePath = null;

    public ?string $publishedAt = null;

    public string $metaTitle = '';

    public string $metaDescription = '';

    public function mount(?int $id = null): void
    {
        if ($id) {
            $this->article = NewsArticle::findOrFail($id);
            $this->title = $this->article->title;
            $this->content = $this->article->content;
            $this->excerpt = $this->article->excerpt ?? '';
            $this->status = $this->article->status;
            $this->currentFeaturedImagePath = $this->article->featured_image_path;
            $this->publishedAt = $this->article->published_at?->format('Y-m-d');
            $this->metaTitle = $this->article->meta_title ?? '';
            $this->metaDescription = $this->article->meta_description ?? '';
        } else {
            // Default to today's date for new articles
            $this->publishedAt = now()->format('Y-m-d');
        }
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'status' => 'required|in:draft,published',
            'featuredImage' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
            'publishedAt' => 'required|date',
            'metaTitle' => 'nullable|string|max:255',
            'metaDescription' => 'nullable|string|max:500',
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'title' => 'judul',
            'content' => 'konten',
            'excerpt' => 'ringkasan',
            'status' => 'status',
            'featuredImage' => 'gambar unggulan',
            'publishedAt' => 'tanggal publikasi',
            'metaTitle' => 'judul SEO',
            'metaDescription' => 'deskripsi SEO',
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        $data = [
            'title' => $this->title,
            'slug' => $this->generateUniqueSlug($this->title),
            'content' => $this->content,
            'excerpt' => $this->excerpt ?: Str::limit(strip_tags($this->content), 200),
            'status' => $this->status,
            'published_at' => $this->publishedAt,
            'meta_title' => $this->metaTitle ?: $this->title,
            'meta_description' => $this->metaDescription ?: Str::limit(strip_tags($this->content), 160),
            'author_id' => auth()->id(),
        ];

        // Handle featured image upload
        if ($this->featuredImage) {
            // Delete old image if exists and we're updating
            if ($this->article && $this->article->featured_image_path) {
                Storage::disk('public')->delete($this->article->featured_image_path);
            }

            $path = $this->featuredImage->store('news', 'public');
            $data['featured_image_path'] = $path;
        }

        if ($this->article) {
            $this->article->update($data);
        } else {
            $this->article = NewsArticle::create($data);
        }

        // Clear news cache after saving
        $cacheService = app(CacheService::class);
        $cacheService->clearNewsCache();

        session()->flash('message', 'Artikel berhasil disimpan.');
        $this->redirect(route('admin.news.index'), navigate: true);
    }

    public function removeFeaturedImage(): void
    {
        if ($this->article && $this->article->featured_image_path) {
            Storage::disk('public')->delete($this->article->featured_image_path);
            $this->article->featured_image_path = null;
            $this->article->save();
            $this->currentFeaturedImagePath = null;

            session()->flash('message', 'Gambar unggulan berhasil dihapus.');
        }
    }

    /**
     * Generate a unique slug from the title.
     */
    private function generateUniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $count = 1;

        // Check if slug exists, excluding current article if updating
        while (NewsArticle::where('slug', $slug)
            ->when($this->article, fn ($query) => $query->where('id', '!=', $this->article->id))
            ->exists()) {
            $slug = $originalSlug.'-'.$count;
            $count++;
        }

        return $slug;
    }
}; ?>

<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ $article ? 'Edit Berita' : 'Tambah Berita' }}</flux:heading>
        <flux:subheading>{{ $article ? 'Perbarui artikel berita' : 'Buat artikel berita baru' }}</flux:subheading>
    </div>

    @if (session()->has('message'))
        <flux:callout color="green" icon="check-circle" class="mb-6">
            {{ session('message') }}
        </flux:callout>
    @endif

    <form wire:submit="save" class="space-y-8">
        {{-- Basic Information --}}
        <div class="space-y-4">
            <flux:heading size="lg">Informasi Dasar</flux:heading>
            <flux:subheading class="mb-4">Informasi utama artikel</flux:subheading>

            <flux:input 
                wire:model="title" 
                label="Judul Artikel" 
                type="text" 
                required 
                placeholder="Masukkan judul artikel"
            />

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <flux:input 
                    wire:model="publishedAt" 
                    label="Tanggal Publikasi" 
                    type="date" 
                    required 
                />

                <flux:select wire:model="status" label="Status" required>
                    <option value="draft">Draft</option>
                    <option value="published">Dipublikasikan</option>
                </flux:select>
            </div>

            <flux:textarea 
                wire:model="excerpt" 
                label="Ringkasan (Opsional)" 
                rows="3" 
                placeholder="Ringkasan singkat artikel. Jika kosong, akan dibuat otomatis dari konten."
            />
        </div>

        <flux:separator />

        {{-- Featured Image --}}
        <div class="space-y-4">
            <flux:heading size="lg">Gambar Unggulan</flux:heading>
            <flux:subheading class="mb-4">Upload gambar utama artikel (maksimal 5MB, format: JPEG, PNG, WebP)</flux:subheading>

            @if ($currentFeaturedImagePath)
                <div class="flex items-start gap-4">
                    <img 
                        src="{{ Storage::url($currentFeaturedImagePath) }}" 
                        alt="Gambar Unggulan" 
                        class="h-48 w-auto rounded-lg border object-cover"
                    >
                    <div class="flex flex-col gap-2">
                        <flux:text>Gambar saat ini</flux:text>
                        <flux:button 
                            wire:click="removeFeaturedImage" 
                            variant="danger" 
                            size="sm"
                            type="button"
                            wire:confirm="Apakah Anda yakin ingin menghapus gambar unggulan?"
                        >
                            Hapus Gambar
                        </flux:button>
                    </div>
                </div>
            @endif

            <div>
                <flux:input 
                    wire:model="featuredImage" 
                    label="Upload Gambar Baru" 
                    type="file" 
                    accept="image/jpeg,image/jpg,image/png,image/webp"
                />
                @if ($featuredImage)
                    <flux:text class="mt-2 text-sm">
                        File dipilih: {{ $featuredImage->getClientOriginalName() }}
                    </flux:text>
                @endif
            </div>

            <div wire:loading wire:target="featuredImage" class="text-sm text-zinc-600 dark:text-zinc-400">
                Mengunggah file...
            </div>
        </div>

        <flux:separator />

        {{-- Content --}}
        <div class="space-y-4">
            <flux:heading size="lg">Konten Artikel</flux:heading>
            <flux:subheading class="mb-4">Tulis konten artikel lengkap</flux:subheading>

            <div>
                <flux:textarea 
                    wire:model="content" 
                    label="Konten" 
                    rows="20" 
                    required 
                    placeholder="Tulis konten artikel di sini..."
                />
                <flux:text class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    Tip: Untuk editor yang lebih canggih dengan formatting, Anda dapat mengintegrasikan TinyMCE atau CKEditor.
                </flux:text>
            </div>
        </div>

        <flux:separator />

        {{-- SEO Metadata --}}
        <div class="space-y-4">
            <flux:heading size="lg">SEO (Opsional)</flux:heading>
            <flux:subheading class="mb-4">Optimasi untuk mesin pencari</flux:subheading>

            <flux:input 
                wire:model="metaTitle" 
                label="Judul SEO" 
                type="text" 
                placeholder="Jika kosong, akan menggunakan judul artikel"
            />

            <flux:textarea 
                wire:model="metaDescription" 
                label="Deskripsi SEO" 
                rows="3" 
                placeholder="Jika kosong, akan dibuat otomatis dari konten"
            />

            <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                Judul SEO sebaiknya 50-60 karakter. Deskripsi SEO sebaiknya 150-160 karakter.
            </flux:text>
        </div>

        {{-- Submit Buttons --}}
        <div class="flex items-center justify-end gap-4 border-t pt-6">
            <flux:button 
                variant="ghost" 
                href="{{ route('admin.news.index') }}"
                wire:navigate
                type="button"
            >
                Batal
            </flux:button>
            <flux:button 
                variant="primary" 
                type="submit" 
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="save">{{ $article ? 'Perbarui Artikel' : 'Simpan Artikel' }}</span>
                <span wire:loading wire:target="save">Menyimpan...</span>
            </flux:button>
        </div>
    </form>
</div>
