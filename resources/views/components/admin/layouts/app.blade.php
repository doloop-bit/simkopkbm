@props([
    'title' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    @php
        $title = $title ? $title . ' - ' . config('app.name', 'Laravel') : config('app.name', 'Laravel');
    @endphp
    
    @include('partials.head')
</head>
<body class="min-h-screen bg-white font-sans antialiased dark:bg-zinc-900">
    <x-admin.sidebar />
    <x-admin.header />

    <flux:main>
        {{ $slot }}
    </flux:main>

    @fluxScripts
</body>
</html>