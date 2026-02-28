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

<div class="p-6 space-y-8 text-slate-900 dark:text-white pb-24 md:pb-6">
    @if (session()->has('message'))
        <x-ui.alert :title="__('Sukses')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100" dismissible>
            {{ session('message') }}
        </x-ui.alert>
    @endif

    <x-ui.header :title="$article ? __('Edit Artikel Berita') : __('Tulis Berita Baru')" :subtitle="$article ? __('Perbarui konten, status, atau metadata artikel.') : __('Siapkan publikasi artikel informasi atau pengumuman sekolah.')" separator />

    <form wire:submit="save" class="space-y-8">
        {{-- Basic Information --}}
        <x-ui.card shadow padding="false" class="border-none ring-1 ring-slate-100 dark:ring-slate-800">
            <div class="p-6 border-b border-slate-50 dark:border-slate-800/50 bg-slate-50/50 dark:bg-slate-900/50">
                <h3 class="font-black text-slate-800 dark:text-white uppercase tracking-tight text-sm italic">{{ __('Informasi & Konfigurasi Dasar') }}</h3>
            </div>
            <div class="p-8 space-y-8">
                <x-ui.input 
                    wire:model="title" 
                    :label="__('Judul Utama Berita')" 
                    type="text" 
                    required 
                    :placeholder="__('Tulis judul yang menarik dan informatif...')"
                    class="font-black text-xl italic uppercase tracking-tighter"
                />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <x-ui.input 
                        wire:model="publishedAt" 
                        :label="__('Tanggal Penayangan')" 
                        type="date" 
                        required 
                        class="font-bold text-slate-600"
                    />

                    <x-ui.select 
                        wire:model="status" 
                        :label="__('Status Publikasi')" 
                        :options="[['id' => 'draft', 'name' => __('Draft (Arsip Lokal)')], ['id' => 'published', 'name' => __('Published (Tampil Live)')]]"
                        required 
                        class="font-bold"
                    />
                </div>

                <x-ui.textarea 
                    wire:model="excerpt" 
                    :label="__('Cuplikan (Snippet)')" 
                    rows="3" 
                    :placeholder="__('Tulis ringkasan singkat untuk menarik pembaca di halaman daftar berita...')"
                    class="italic text-sm leading-relaxed"
                />
                <p class="text-[10px] text-slate-400 italic px-1">
                    * {{ __('Jika dikosongkan, sistem akan mengambil secara otomatis dari 200 karakter pertama isi artikel.') }}
                </p>
            </div>
        </x-ui.card>

        {{-- Featured Image --}}
        <x-ui.card shadow padding="false" class="border-none ring-1 ring-slate-100 dark:ring-slate-800">
            <div class="p-6 border-b border-slate-50 dark:border-slate-800/50 bg-slate-50/50 dark:bg-slate-900/50">
                <h3 class="font-black text-slate-800 dark:text-white uppercase tracking-tight text-sm italic">{{ __('Visual & Poster Berita') }}</h3>
            </div>
            <div class="p-8 space-y-6">
                @if ($currentFeaturedImagePath)
                    <div class="flex items-center gap-6 p-6 bg-slate-50 dark:bg-slate-900/50 rounded-3xl border border-slate-100 dark:border-slate-800 group">
                        <div class="relative overflow-hidden rounded-2xl shadow-xl ring-4 ring-white dark:ring-slate-800">
                            <img 
                                src="{{ Storage::url($currentFeaturedImagePath) }}" 
                                alt="Gambar Unggulan" 
                                class="h-48 w-80 object-cover group-hover:scale-105 transition-transform duration-700"
                            >
                        </div>
                        <div class="flex-1">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest italic mb-2 block">{{ __('Gambar Utama Saat Ini') }}</span>
                            <x-ui.button 
                                wire:click="removeFeaturedImage" 
                                :label="__('Hapus Gambar')"
                                icon="o-trash"
                                class="btn-ghost btn-xs text-rose-500 hover:bg-rose-50 font-bold"
                                wire:confirm="__('Apakah Anda yakin ingin menghapus gambar unggulan ini?')"
                            />
                        </div>
                    </div>
                @endif

                <x-ui.file 
                    wire:model="featuredImage" 
                    :label="__('Unggah Foto Sampul Berita')" 
                    accept="image/jpeg,image/jpg,image/png,image/webp"
                    class="bg-white dark:bg-slate-800"
                >
                    @if ($featuredImage)
                        <div class="text-[10px] font-black italic text-indigo-600 mt-2 px-1">
                            {{ __('File dipilih') }}: <span class="underline">{{ $featuredImage->getClientOriginalName() }}</span>
                        </div>
                    @endif
                </x-ui.file>
                <p class="text-[10px] text-slate-400 italic px-1 leading-relaxed">
                    * {{ __('Ukuran maksimal 5MB. Format: JPEG, PNG, WebP. Direkomendasikan rasio 16:9 untuk tampilan optimal di beranda.') }}
                </p>
            </div>
        </x-ui.card>

        {{-- Content --}}
        <x-ui.card shadow padding="false" class="border-none ring-1 ring-primary/5">
            <div class="p-6 border-b border-slate-50 dark:border-slate-800/50 bg-slate-50/50 dark:bg-slate-900/50 flex items-center justify-between">
                <h3 class="font-black text-slate-800 dark:text-white uppercase tracking-tight text-sm italic">{{ __('Editor Isi Artikel') }}</h3>
                <x-ui.badge :label="__('Editor Terintegrasi')" class="bg-indigo-50 text-indigo-600 border-none font-black italic text-[9px] px-3" />
            </div>
            <div class="p-8 space-y-6">
                <x-ui.textarea 
                    wire:model="content" 
                    :label="__('Badan Artikel')" 
                    rows="20" 
                    required 
                    :placeholder="__('Tulis narasi lengkap berita di sini...')"
                    class="font-medium text-slate-700 dark:text-slate-300 leading-relaxed text-base"
                />
                
                <div class="flex items-start gap-4 p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800">
                    <x-ui.icon name="o-information-circle" class="size-5 text-indigo-500 shrink-0" />
                    <div>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest italic mb-1">{{ __('Panduan Editor') }}</p>
                        <p class="text-[11px] text-slate-400 italic leading-relaxed">
                            {{ __('Gunakan format paragraf yang jelas. Anda dapat menyisipkan HTML sederhana jika diperlukan untuk pembentukan struktur teks yang lebih kompleks.') }}
                        </p>
                    </div>
                </div>
            </div>
        </x-ui.card>

        {{-- SEO Metadata --}}
        <x-ui.card shadow padding="false" class="border-none ring-1 ring-slate-100 dark:ring-slate-800">
            <div class="p-6 border-b border-slate-50 dark:border-slate-800/50 bg-slate-50/50 dark:bg-slate-900/50">
                <h3 class="font-black text-slate-800 dark:text-white uppercase tracking-tight text-sm italic">{{ __('Optimasi Mesin Pencari (SEO)') }}</h3>
            </div>
            <div class="p-8 space-y-8">
                <x-ui.input 
                    wire:model="metaTitle" 
                    :label="__('Judul SEO (Browser Title)')" 
                    type="text" 
                    :placeholder="__('Contoh: Berita Terbaru Hari Ini | Nama Sekolah')"
                    class="font-bold"
                />
                <p class="text-[10px] text-slate-400 italic px-1 -mt-6">
                    * {{ __('Jika dikosongkan, akan menggunakan judul artikel di atas. Disarankan 50-60 karakter.') }}
                </p>

                <x-ui.textarea 
                    wire:model="metaDescription" 
                    :label="__('Deskripsi SEO (Search Snippet)')" 
                    rows="3" 
                    :placeholder="__('Tulis deskripsi yang mengundang klik bagi pencari berita di Google...')"
                    class="text-xs italic"
                />
                <p class="text-[10px] text-slate-400 italic px-1 -mt-6">
                    * {{ __('Jika dikosongkan, akan mengambil otomatis dari konten. Disarankan 150-160 karakter untuk hasil optimal di mesin cari.') }}
                </p>
            </div>
        </x-ui.card>

        {{-- Submit Buttons --}}
        <div class="flex items-center justify-end gap-3 pt-8 pb-12">
            <x-ui.button 
                :label="__('Batalkan & Kembali')"
                link="{{ route('admin.news.index') }}"
                class="btn-ghost"
            />
            <x-ui.button 
                :label="$article ? __('Perbarui Publikasi') : __('Terbitkan Berita Sekarang')"
                class="btn-primary shadow-xl shadow-primary/20 px-8" 
                type="submit" 
                spinner="save"
            />
        </div>
    </form>
</div>
