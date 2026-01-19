@props([
    'title' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full dark">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    @php
        $title = $title ? $title . ' - ' . config('app.name', 'Laravel') : config('app.name', 'Laravel');
    @endphp
    
    @include('partials.head')
</head>
<body class="h-full font-sans antialiased bg-white dark:bg-zinc-900">
    <div class="flex h-full">
        {{-- Sidebar component (handles both desktop and mobile) --}}
        <x-admin.sidebar />
        
        <div class="flex-1 flex flex-col">
            {{-- Mobile header with user menu --}}
            <x-admin.header />

            {{-- Main content --}}
            <flux:main class="transition-opacity duration-200">
                {{ $slot }}
            </flux:main>
        </div>
    </div>

    @fluxScripts
</body>
</html>