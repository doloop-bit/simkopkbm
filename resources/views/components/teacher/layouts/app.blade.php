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
<body class="min-h-screen bg-white font-sans antialiased dark:bg-zinc-900"
      x-data="{ sidebarOpen: false }">

    {{-- Mobile Sidebar Overlay --}}
    <div x-show="sidebarOpen" x-cloak
         x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm lg:hidden"
         @click="sidebarOpen = false">
    </div>

    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside
            x-cloak
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-50 w-64 bg-base-200 transition-transform duration-300 lg:translate-x-0 lg:static lg:z-auto flex flex-col"
        >
            <div class="flex-1 overflow-y-auto">
                <x-teacher.sidebar />
            </div>
            <div class="px-4 pb-4">
                <x-admin.desktop-user-menu />
            </div>
        </aside>

        {{-- Main Content --}}
        <div class="flex-1 min-w-0 lg:ml-0">
            <x-admin.header />

            @if(request()->routeIs('teacher.report-cards') || request()->routeIs('teacher.assessments.grading') || request()->routeIs('teacher.assessments.attendance') || request()->routeIs('teacher.assessments.extracurricular'))
                <x-teacher.report-card-nav />
            @endif

            <div class="pb-20 md:pb-0">
                {{ $slot }}
            </div>
        </div>
    </div>

    <x-teacher.bottom-nav />
    <x-ui.toast />
</body>
</html>
