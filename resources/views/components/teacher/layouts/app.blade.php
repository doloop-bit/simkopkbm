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
    <x-main full-width>
        {{-- Sidebar --}}
        <x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-200">
            <x-teacher.sidebar />
            <x-slot:sidebar-footer>
                <x-admin.desktop-user-menu />
            </x-slot:sidebar-footer>
        </x-slot:sidebar>

        {{-- Content --}}
        <x-slot:content>
            <x-admin.header />

            @if(request()->routeIs('teacher.report-cards') || request()->routeIs('teacher.assessments.grading') || request()->routeIs('teacher.assessments.attendance') || request()->routeIs('teacher.assessments.extracurricular'))
                <x-teacher.report-card-nav />
            @endif

            <div class="pb-20 md:pb-0">
                {{ $slot }}
            </div>
        </x-slot:content>
    </x-main>

    <x-teacher.bottom-nav />
    <x-toast />
</body>
</html>
