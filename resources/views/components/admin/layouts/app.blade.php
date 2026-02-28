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

    <style>
        /* INLINE BRUTE FORCE PATCH FOR SIDEBAR */
        .sidebar-premium .menu li > a.active,
        .sidebar-premium .menu li > details > summary.active,
        .sidebar-premium .menu li > .active {
            background-color: #059669 !important;
            color: #ffffff !important;
        }
        .sidebar-premium .menu li > a.active *,
        .sidebar-premium .menu li > details > summary.active * {
            color: #ffffff !important;
        }
        .sidebar-premium .menu li > details[open] > summary {
            background-color: rgba(30, 41, 59, 0.4) !important;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-mesh font-sans antialiased text-slate-900 dark:text-slate-100 selection:bg-emerald-500/30"
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
            class="fixed inset-y-0 left-0 z-50 w-64 sidebar-premium transition-transform duration-300 lg:translate-x-0 lg:static lg:z-auto flex flex-col"
        >
            {{-- Sidebar Content --}}
            <div class="flex-1 overflow-y-auto">
                <x-admin.sidebar />
            </div>

            {{-- User & Settings --}}
            <div class="mt-auto px-4 pb-4">
                <x-ui.menu-separator class="my-4 border-slate-700/50" />
                <x-admin.desktop-user-menu />
            </div>
        </aside>

        {{-- Main Content --}}
        <div class="flex-1 min-w-0 lg:ml-0">
            <div class="min-h-screen flex flex-col">
                <x-admin.header />

                <div class="p-responsive flex-1">
                    @if(request()->routeIs('admin.school-profile.*') || request()->routeIs('admin.news.*') || request()->routeIs('admin.gallery.*') || request()->routeIs('admin.programs.*') || request()->routeIs('admin.contact-inquiries.*'))
                        <div class="mb-6"><x-admin.konten-web-nav /></div>
                    @endif

                    @if(request()->routeIs('admin.report-card.*') || request()->routeIs('admin.assessments.attendance') || request()->routeIs('admin.assessments.extracurricular'))
                        <div class="mb-6"><x-admin.report-card-nav /></div>
                    @endif

                    @if(request()->routeIs('financial.*'))
                        <div class="mb-6"><x-admin.keuangan-nav /></div>
                    @endif

                    <div class="max-w-7xl mx-auto">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-ui.toast />
</body>
</html>
