@props([
    'title' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    @php
        $title = $title ? $title . ' - ' . config('app.name', 'Laravel') : config('app.name', 'Laravel');
    @endphp
    
    
    @include('partials.head')
</head>
<body class="min-h-screen bg-white font-sans antialiased dark:bg-zinc-900">
    <x-main full-width>
        {{-- Sidebar --}}
        <x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-200">
            <x-admin.sidebar />
        </x-slot:sidebar>

        {{-- Content --}}
        <x-slot:content>
            <x-admin.header />

            @if(request()->routeIs('admin.school-profile.*') || request()->routeIs('admin.news.*') || request()->routeIs('admin.gallery.*') || request()->routeIs('admin.programs.*') || request()->routeIs('admin.contact-inquiries.*'))
                <x-admin.konten-web-nav />
            @endif

            @if(request()->routeIs('admin.report-card.*') || request()->routeIs('admin.assessments.attendance') || request()->routeIs('admin.assessments.extracurricular'))
                <x-admin.report-card-nav />
            @endif

            @if(request()->routeIs('financial.*'))
                <x-admin.keuangan-nav />
            @endif

            {{ $slot }}
        </x-slot:content>
    </x-main>

    {{-- Mary UI scripts included automatically via layout components --}}
</body>
</html>