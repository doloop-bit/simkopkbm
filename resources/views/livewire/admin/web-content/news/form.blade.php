<?php

use App\Models\NewsArticle;
use App\Services\CacheService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
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

<div class="p-6">
    <x-header title="{{ $article ? 'Edit Berita' : 'Tambah Berita' }}" subtitle="{{ $article ? 'Perbarui artikel berita' : 'Buat artikel berita baru' }}" separator />

    @if (session()->has('message'))
        <x-alert title="Sukses" icon="o-check-circle" class="alert-success mb-6">
            {{ session('message') }}
        </x-alert>
    @endif

    <form wire:submit="save" class="space-y-8">
        {{-- Basic Information --}}
        <x-card title="Informasi Dasar" subtitle="Informasi utama artikel" separator shadow>
            <div class="space-y-4">
                <x-input 
                    wire:model="title" 
                    label="Judul Artikel" 
                    type="text" 
                    required 
                    placeholder="Masukkan judul artikel"
                />

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-input 
                        wire:model="publishedAt" 
                        label="Tanggal Publikasi" 
                        type="date" 
                        required 
                    />

                    <x-select 
                        wire:model="status" 
                        label="Status" 
                        :options="[['id' => 'draft', 'name' => 'Draft'], ['id' => 'published', 'name' => 'Dipublikasikan']]"
                        required 
                    />
                </div>

                <x-textarea 
                    wire:model="excerpt" 
                    label="Ringkasan (Opsional)" 
                    rows="3" 
                    placeholder="Ringkasan singkat artikel. Jika kosong, akan dibuat otomatis dari konten."
                />
            </div>
        </x-card>

        {{-- Featured Image --}}
        <x-card title="Gambar Unggulan" subtitle="Upload gambar utama artikel (maksimal 5MB, format: JPEG, PNG, WebP)" separator shadow>
            <div class="space-y-6">
                @if ($currentFeaturedImagePath)
                    <div class="flex items-start gap-4 p-4 bg-base-200 rounded-lg">
                        <img 
                            src="{{ Storage::url($currentFeaturedImagePath) }}" 
                            alt="Gambar Unggulan" 
                            class="h-48 w-auto rounded-lg border border-base-300 object-cover bg-white"
                        >
                        <div class="flex flex-col gap-2">
                            <span class="text-sm font-medium">Gambar saat ini</span>
                            <x-button 
                                wire:click="removeFeaturedImage" 
                                label="Hapus Gambar"
                                icon="o-trash"
                                class="btn-error btn-sm"
                                wire:confirm="Apakah Anda yakin ingin menghapus gambar unggulan?"
                            />
                        </div>
                    </div>
                @endif

                <x-file 
                    wire:model="featuredImage" 
                    label="Upload Gambar Baru" 
                    accept="image/jpeg,image/jpg,image/png,image/webp"
                    crop-after-change
                >
                    @if ($featuredImage)
                        <div class="text-sm mt-2">
                            File dipilih: <span class="font-medium">{{ $featuredImage->getClientOriginalName() }}</span>
                        </div>
                    @endif
                </x-file>
            </div>
        </x-card>

        {{-- Content --}}
        <x-card title="Konten Artikel" subtitle="Tulis konten artikel lengkap" separator shadow>
            <div class="space-y-4">
                <x-textarea 
                    wire:model="content" 
                    label="Konten" 
                    rows="20" 
                    required 
                    placeholder="Tulis konten artikel di sini..."
                />
                <x-alert icon="o-information-circle" class="bg-base-200 border-base-300">
                    Tip: Gunakan Markdown atau HTML sederhana untuk memformat konten.
                </x-alert>
            </div>
        </x-card>

        {{-- SEO Metadata --}}
        <x-card title="SEO (Opsional)" subtitle="Optimasi untuk mesin pencari" separator shadow>
            <div class="space-y-4">
                <x-input 
                    wire:model="metaTitle" 
                    label="Judul SEO" 
                    type="text" 
                    placeholder="Jika kosong, akan menggunakan judul artikel"
                    hint="Disarankan 50-60 karakter"
                />

                <x-textarea 
                    wire:model="metaDescription" 
                    label="Deskripsi SEO" 
                    rows="3" 
                    placeholder="Jika kosong, akan dibuat otomatis dari konten"
                    hint="Disarankan 150-160 karakter"
                />
            </div>
        </x-card>

        {{-- Submit Buttons --}}
        <div class="flex items-center justify-end gap-3 pt-6">
            <x-button 
                label="Batal"
                link="{{ route('admin.news.index') }}"
                ghost
            />
            <x-button 
                label="{{ $article ? 'Perbarui Artikel' : 'Simpan Artikel' }}"
                class="btn-primary" 
                type="submit" 
                spinner="save"
            />
        </div>
    </form>
</div>
