<?php

use App\Models\NewsArticle;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.public')] class extends Component
{
    use WithPagination;

    public function with(): array
    {
        return [
            'articles' => NewsArticle::published()
                ->latest()
                ->with('author')
                ->paginate(9),
            'title' => 'Berita - ' . config('app.name'),
            'description' => 'Baca berita terbaru dan informasi terkini dari ' . config('app.name') . ' - Pusat Kegiatan Belajar Masyarakat.',
            'keywords' => 'Berita, Informasi, Pengumuman, Kegiatan, PKBM, Berita Sekolah',
            'ogTitle' => 'Berita - ' . config('app.name'),
            'ogDescription' => 'Baca berita terbaru dan informasi terkini dari ' . config('app.name') . ' - Pusat Kegiatan Belajar Masyarakat.',
        ];
    }
}; ?>

<div>
    {{-- Hero Section --}}
    <div class="bg-gradient-to-r from-red-600 to-red-800 py-16 text-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="mb-4 text-4xl font-bold md:text-5xl">Berita & Artikel</h1>
                <p class="text-xl text-red-100">
                    Informasi terkini seputar kegiatan dan perkembangan sekolah
                </p>
            </div>
        </div>
    </div>

    {{-- News Articles --}}
    <div class="py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if ($articles->count() > 0)
                <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($articles as $article)
                        <article class="group overflow-hidden rounded-lg bg-white shadow-lg transition-transform duration-300 hover:scale-105">
                            {{-- Featured Image --}}
                            <div class="aspect-video overflow-hidden bg-gray-200">
                                @if ($article->featured_image_path)
                                    <img 
                                        src="{{ Storage::url($article->featured_image_path) }}" 
                                        alt="{{ $article->title }}"
                                        class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-110"
                                    >
                                @else
                                    <div class="flex h-full items-center justify-center bg-gray-100">
                                        <svg class="h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>

                            {{-- Content --}}
                            <div class="p-6">
                                {{-- Meta Info --}}
                                <div class="mb-3 flex items-center gap-4 text-sm text-gray-500">
                                    <time datetime="{{ $article->published_at->format('Y-m-d') }}">
                                        {{ $article->published_at->format('d M Y') }}
                                    </time>
                                    @if ($article->author)
                                        <span>•</span>
                                        <span>{{ $article->author->name }}</span>
                                    @endif
                                </div>

                                {{-- Title --}}
                                <h2 class="mb-3 text-xl font-bold text-gray-900 group-hover:text-red-600">
                                    <a href="{{ route('public.news.show', $article->slug) }}">
                                        {{ $article->title }}
                                    </a>
                                </h2>

                                {{-- Excerpt --}}
                                <p class="mb-4 text-gray-600 leading-relaxed">
                                    {{ $article->excerpt ?: Str::limit(strip_tags($article->content), 120) }}
                                </p>

                                {{-- Read More --}}
                                <a 
                                    href="{{ route('public.news.show', $article->slug) }}" 
                                    class="inline-flex items-center gap-2 text-sm font-medium text-red-600 hover:text-red-800"
                                >
                                    Baca Selengkapnya
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if ($articles->hasPages())
                    <div class="mt-12">
                        {{ $articles->links() }}
                    </div>
                @endif
            @else
                {{-- Empty State --}}
                <div class="py-16 text-center">
                    <svg class="mx-auto h-24 w-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                    </svg>
                    <h3 class="mt-4 text-xl font-medium text-gray-900">Belum Ada Berita</h3>
                    <p class="mt-2 text-gray-600">Berita dan artikel akan segera hadir.</p>
                </div>
            @endif
        </div>
    </div>
</div>