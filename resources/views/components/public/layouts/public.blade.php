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

    <!-- DNS Prefetch for common domains -->
    <link rel="dns-prefetch" href="https://fonts.bunny.net">
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    
    <!-- Bunny Fonts (privacy-friendly, GDPR compliant) -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700|instrument-sans:400,500,600&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="antialiased bg-gradient-to-br from-green-50 via-white to-emerald-50 min-h-screen">
    <!-- Navigation -->
    <x-public.navbar />

    <!-- Main Content -->
    <main class="min-h-screen">
        {{ $slot }}
    </main>

    <!-- Footer -->
    <x-public.footer />
</body>
</html>