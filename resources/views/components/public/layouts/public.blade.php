<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name') }}</title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="{{ $description ?? 'SIMKOPKBM - Sistem Informasi Manajemen Sekolah untuk Pusat Kegiatan Belajar Masyarakat. Menyediakan pendidikan berkualitas untuk semua.' }}">
    <meta name="keywords" content="{{ $keywords ?? 'PKBM, Pusat Kegiatan Belajar Masyarakat, Pendidikan, PAUD, Paket A, Paket B, Paket C, Sekolah' }}">
    <meta name="author" content="{{ config('app.name') }}">
    <link rel="canonical" href="{{ $canonical ?? request()->url() }}">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="{{ $ogTitle ?? $title ?? config('app.name') }}">
    <meta property="og:description" content="{{ $ogDescription ?? $description ?? 'SIMKOPKBM - Sistem Informasi Manajemen Sekolah untuk Pusat Kegiatan Belajar Masyarakat. Menyediakan pendidikan berkualitas untuk semua.' }}">
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:url" content="{{ $ogUrl ?? request()->url() }}">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:locale" content="id_ID">
    @if(isset($ogImage) && $ogImage)
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:alt" content="{{ $ogImageAlt ?? $ogTitle ?? $title ?? config('app.name') }}">
    @endif
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="{{ $twitterCard ?? 'summary_large_image' }}">
    <meta name="twitter:title" content="{{ $twitterTitle ?? $ogTitle ?? $title ?? config('app.name') }}">
    <meta name="twitter:description" content="{{ $twitterDescription ?? $ogDescription ?? $description ?? 'SIMKOPKBM - Sistem Informasi Manajemen Sekolah untuk Pusat Kegiatan Belajar Masyarakat. Menyediakan pendidikan berkualitas untuk semua.' }}">
    @if(isset($twitterImage) && $twitterImage)
    <meta name="twitter:image" content="{{ $twitterImage ?? $ogImage }}">
    <meta name="twitter:image:alt" content="{{ $twitterImageAlt ?? $ogImageAlt ?? $twitterTitle ?? $title ?? config('app.name') }}">
    @endif

    <!-- Fonts Optimization (Self-hosted) -->
    <link rel="preload" as="style" href="{{ asset('fonts/fonts.css') }}" />
    <link rel="stylesheet" href="{{ asset('fonts/fonts.css') }}" media="print" onload="this.media='all'" />

    
    <tallstackui:script />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        body { font-family: 'Open Sans', sans-serif; }
        h1, h2, h3, h4, h5, h6, .font-heading { font-family: 'Montserrat', sans-serif; }
        ::selection { background-color: #fbbf24; color: #0f172a; }
    </style>
</head>
<body class="antialiased bg-gray-50 min-h-screen">
    <!-- Navigation -->
    <x-public.navbar />

    <!-- Main Content -->
    <main class="min-h-screen">
        {{ $slot }}
    </main>

    <!-- Footer -->
    <x-public.footer />

    <!-- WhatsApp Floating Button -->
    @php
        $schoolProfileForWa = app(\App\Services\CacheService::class)->getSchoolProfile();
        $waPhone = $schoolProfileForWa?->phone ?? '6281234567890'; // Default fallback phone
    @endphp
    @if($waPhone)
        <a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $waPhone)) }}"
           target="_blank"
           class="fixed bottom-6 right-6 z-[9999] flex items-center justify-center w-14 h-14 bg-green-500 text-white rounded-full shadow-lg hover:bg-green-600 hover:scale-110 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 animate-bounce"
           style="animation-duration: 3s;"
           aria-label="Chat WhatsApp">
            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"/>
            </svg>
        </a>
    @endif
</body>
</html>