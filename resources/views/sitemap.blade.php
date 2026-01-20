<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
    <!-- Homepage -->
    <url>
        <loc>{{ route('home') }}</loc>
        <lastmod>{{ now()->toISOString() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    
    <!-- About Pages -->
    <url>
        <loc>{{ route('public.about') }}</loc>
        <lastmod>{{ $schoolProfile?->updated_at?->toISOString() ?? now()->toISOString() }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    
    <url>
        <loc>{{ route('public.organizational-structure') }}</loc>
        <lastmod>{{ $schoolProfile?->updated_at?->toISOString() ?? now()->toISOString() }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    
    <url>
        <loc>{{ route('public.facilities') }}</loc>
        <lastmod>{{ $schoolProfile?->updated_at?->toISOString() ?? now()->toISOString() }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    
    <!-- Programs -->
    <url>
        <loc>{{ route('public.programs.index') }}</loc>
        <lastmod>{{ $programs->max('updated_at')?->toISOString() ?? now()->toISOString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    
    @foreach($programs as $program)
    <url>
        <loc>{{ route('public.programs.show', $program->slug) }}</loc>
        <lastmod>{{ $program->updated_at->toISOString() }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    @endforeach
    
    <!-- News -->
    <url>
        <loc>{{ route('public.news.index') }}</loc>
        <lastmod>{{ $news->max('updated_at')?->toISOString() ?? now()->toISOString() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    
    @foreach($news as $article)
    <url>
        <loc>{{ route('public.news.show', $article->slug) }}</loc>
        <lastmod>{{ $article->updated_at->toISOString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    @endforeach
    
    <!-- Gallery -->
    <url>
        <loc>{{ route('public.gallery') }}</loc>
        <lastmod>{{ now()->toISOString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.6</priority>
    </url>
    
    <!-- Contact -->
    <url>
        <loc>{{ route('public.contact') }}</loc>
        <lastmod>{{ $schoolProfile?->updated_at?->toISOString() ?? now()->toISOString() }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
</urlset>