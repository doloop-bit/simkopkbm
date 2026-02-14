<?php

use App\Models\NewsArticle;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Storage;

new #[Layout('components.public.layouts.public')] class extends Component
{
    public NewsArticle $article;

    public function mount(string $slug): void
    {
        $this->article = NewsArticle::where('slug', $slug)
            ->where('status', 'published')
            ->with('author')
            ->firstOrFail();
    }

    public function with(): array
    {
        // Set SEO data dynamically
        $title = $this->article->title . ' - ' . config('app.name');
        $description = $this->article->excerpt ?: 
            substr(strip_tags($this->article->content), 0, 160) . '...';
        
        return [
            'relatedArticles' => NewsArticle::published()
                ->where('id', '!=', $this->article->id)
                ->latest()
                ->limit(3)
                ->get(),
            'title' => $title,
            'description' => $description,
            'keywords' => 'Berita, ' . $this->article->title . ', Informasi, PKBM, ' . config('app.name'),
            'ogTitle' => $title,
            'ogDescription' => $description,
            'ogType' => 'article',
            'ogImage' => $this->article->featured_image_path ? Storage::url($this->article->featured_image_path) : null,
            'ogImageAlt' => $this->article->title,
        ];
    }
}; ?>

<div>
    <div x-data="{ lightboxOpen: false }">
    {{-- Hero Section --}}
    <div class="bg-slate-900 py-16 text-white relative">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 relative z-10">
            <nav class="mb-6 flex justify-center" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-2 text-slate-400">
                    <li>
                        <a href="{{ route('public.news.index') }}" class="hover:text-amber-400 transition-colors">
                            Berita
                        </a>
                    </li>
                    <li>
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </li>
                    <li class="text-white">{{ Str::limit($article->title, 50) }}</li>
                </ol>
            </nav>
            
            <div class="text-center">
                <h1 class="mb-4 text-3xl font-bold md:text-4xl font-heading">{{ $article->title }}</h1>
                <div class="flex items-center justify-center gap-4 text-slate-300">
                    <time datetime="{{ $article->published_at->format('Y-m-d') }}">
                        {{ $article->published_at->format('d F Y') }}
                    </time>
                    @if ($article->author)
                        <span>•</span>
                        <span>{{ $article->author->name }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="py-16">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <article class="prose prose-lg max-w-none">
                {{-- Featured Image --}}
                @if ($article->featured_image_path)
                    <div class="mb-8 overflow-hidden rounded-lg cursor-pointer group" @click="lightboxOpen = true">
                        <img 
                            src="{{ Storage::url($article->featured_image_path) }}" 
                            alt="{{ $article->title }}"
                            class="h-64 w-full object-cover md:h-80 transition hover:opacity-95"
                        >
                        <div class="mt-2 text-center text-sm text-slate-500 italic opacity-0 group-hover:opacity-100 transition">Klik untuk memperbesar</div>
                    </div>
                @endif

                {{-- Excerpt --}}
                @if ($article->excerpt)
                    <div class="mb-8 rounded-lg bg-slate-50 p-6 border-l-4 border-amber-500">
                        <p class="text-lg font-medium text-slate-800 leading-relaxed">
                            {{ $article->excerpt }}
                        </p>
                    </div>
                @endif

                {{-- Content --}}
                <div class="text-slate-700">
                    {!! nl2br(e($article->content)) !!}
                </div>
            </article>

            {{-- Share Buttons --}}
            <div class="mt-12 border-t border-slate-200 pt-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-slate-900 font-heading">Bagikan Artikel</h3>
                        <p class="text-sm text-slate-600">Sebarkan informasi ini kepada yang lain</p>
                    </div>
                    <div class="flex gap-3">
                        {{-- Facebook --}}
                        <a 
                            href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->url()) }}" 
                            target="_blank"
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-600 text-white hover:bg-blue-700"
                        >
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </a>

                        {{-- Twitter --}}
                        <a 
                            href="https://twitter.com/intent/tweet?url={{ urlencode(request()->url()) }}&text={{ urlencode($article->title) }}" 
                            target="_blank"
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-sky-500 text-white hover:bg-sky-600"
                        >
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                            </svg>
                        </a>

                        {{-- WhatsApp --}}
                        <a 
                            href="https://wa.me/?text={{ urlencode($article->title . ' - ' . request()->url()) }}" 
                            target="_blank"
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-green-500 text-white hover:bg-green-600"
                        >
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Related Articles --}}
    @if ($relatedArticles->isNotEmpty())
        <div class="border-t border-slate-200 bg-slate-50 py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h2 class="mb-8 text-center text-2xl font-bold font-heading text-slate-900">Berita Lainnya</h2>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    @foreach ($relatedArticles as $relatedArticle)
                        <article class="group overflow-hidden rounded-lg bg-white shadow-md transition-transform duration-300 hover:scale-105">
                            {{-- Featured Image --}}
                            <div class="aspect-video overflow-hidden bg-slate-200">
                                @if ($relatedArticle->featured_image_path)
                                    <img 
                                        src="{{ Storage::url($relatedArticle->featured_image_path) }}" 
                                        alt="{{ $relatedArticle->title }}"
                                        class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-110"
                                    >
                                @else
                                    <div class="flex h-full items-center justify-center bg-slate-100">
                                        <svg class="h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>

                            {{-- Content --}}
                            <div class="p-4">
                                <time class="text-sm text-slate-500">
                                    {{ $relatedArticle->published_at->format('d M Y') }}
                                </time>
                                <h3 class="mt-2 text-lg font-semibold text-slate-900 group-hover:text-amber-600">
                                    <a href="{{ route('public.news.show', $relatedArticle->slug) }}">
                                        {{ Str::limit($relatedArticle->title, 60) }}
                                    </a>
                                </h3>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Lightbox for Featured Image --}}
    @if ($article->featured_image_path)
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
            @keydown.escape.window="lightboxOpen = false"
        >
            <button 
                @click="lightboxOpen = false"
                class="absolute top-4 right-4 text-white/70 hover:text-white z-50 p-2 rounded-full hover:bg-white/10 transition"
            >
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <div class="relative w-full h-full flex items-center justify-center p-4 md:p-12" @click.outside="lightboxOpen = false">
                <img 
                    src="{{ Storage::url($article->featured_image_path) }}" 
                    alt="{{ $article->title }}"
                    class="max-w-full max-h-[90vh] object-contain shadow-2xl rounded-sm"
                >
            </div>
        </div>
    @endif
</div>