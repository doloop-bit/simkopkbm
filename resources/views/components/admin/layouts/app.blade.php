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

        /* Custom Scrollbar for Sidebar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 5px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.2);
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(148, 163, 184, 0.4);
        }
        .custom-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: rgba(148, 163, 184, 0.2) transparent;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-mesh font-sans antialiased text-slate-900 dark:text-slate-100 selection:bg-emerald-500/30"
      x-data="{ sidebarOpen: false, sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true' }">

    {{-- Mobile Sidebar Overlay --}}
    <div x-show="sidebarOpen" x-cloak
         x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm lg:hidden"
         @click="sidebarOpen = false">
    </div>

    <div class="flex min-h-screen relative">
        {{-- Sidebar --}}
        <aside
            x-cloak
            :class="[
                sidebarOpen ? 'translate-x-0' : '-translate-x-full',
                sidebarCollapsed ? 'w-20' : 'w-64'
            ]"
            class="fixed inset-y-0 left-0 z-50 sidebar-premium transition-all duration-300 lg:translate-x-0 flex flex-col h-screen"
        >
            {{-- Fixed Logo Header --}}
            <div class="px-5 py-6 shrink-0 relative group h-[88px]">
                <div class="flex items-center justify-between h-full">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 no-underline min-w-0 transition-opacity" :class="sidebarCollapsed ? 'opacity-100 group-hover:opacity-0 justify-center w-full' : ''" wire:navigate>
                        <div class="shrink-0 flex items-center justify-center">
                            <x-global.app-logo-icon class="size-8 fill-primary block aspect-square object-contain" />
                        </div>
                        <span x-show="!sidebarCollapsed" class="text-xl font-extrabold text-slate-100 whitespace-nowrap overflow-hidden tracking-tight">{{ config('app.name') }}</span>
                    </a>

                    {{-- Toggle Button Expanded --}}
                    <button x-show="!sidebarCollapsed" @click="sidebarCollapsed = true; localStorage.setItem('sidebarCollapsed', 'true')" class="hidden lg:block p-1.5 rounded-lg text-slate-500 hover:bg-slate-800 hover:text-white transition-colors">
                        <x-ui.icon name="o-chevron-double-left" class="w-5 h-5" />
                    </button>
                </div>

                {{-- Toggle Button Collapsed (Hover over logo) --}}
                <div x-show="sidebarCollapsed" class="absolute inset-0 hidden lg:flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                    <button @click="sidebarCollapsed = false; localStorage.setItem('sidebarCollapsed', 'false')" class="p-2 rounded-lg bg-slate-800 text-slate-300 hover:text-white transition-colors shadow-lg">
                        <x-ui.icon name="o-chevron-double-right" class="w-5 h-5" />
                    </button>
                </div>
            </div>

            {{-- Scrollable Navigation --}}
            <div class="flex-1 overflow-y-auto custom-scrollbar px-2">
                <x-admin.sidebar :only-menu="true" />
            </div>

            {{-- User & Settings (Fixed at bottom) --}}
            <div class="mt-auto px-4 pb-4 shrink-0">
                <x-ui.menu-separator class="my-4 border-slate-700/50" />
                
                {{-- Theme Toggle --}}
                <button 
                    x-data="{ 
                        isDark: document.documentElement.classList.contains('dark'),
                        toggleTheme() {
                            this.isDark = !this.isDark;
                            if (this.isDark) {
                                document.documentElement.classList.add('dark');
                                localStorage.setItem('theme', 'dark');
                            } else {
                                document.documentElement.classList.remove('dark');
                                localStorage.setItem('theme', 'light');
                            }
                        }
                    }"
                    @click="toggleTheme()"
                    class="flex items-center w-full px-2 py-2 mb-2 rounded-xl text-slate-400 hover:text-slate-200 hover:bg-slate-800/50 transition-colors"
                    :class="sidebarCollapsed ? 'justify-center' : 'gap-3'"
                    title="Toggle Theme"
                >
                    <span x-show="isDark" x-cloak class="flex items-center justify-center shrink-0">
                        <x-ui.icon name="o-sun" class="w-6 h-6" />
                    </span>
                    <span x-show="!isDark" x-cloak class="flex items-center justify-center shrink-0">
                        <x-ui.icon name="o-moon" class="w-6 h-6" />
                    </span>
                    <span x-show="!sidebarCollapsed" class="text-sm font-semibold truncate text-left flex-1" x-cloak>
                        <span x-show="isDark">Mode Terang</span>
                        <span x-show="!isDark">Mode Gelap</span>
                    </span>
                </button>

                <x-admin.desktop-user-menu />
            </div>
        </aside>

        {{-- Main Content --}}
        <div :class="sidebarCollapsed ? 'lg:ml-20' : 'lg:ml-64'" class="flex-1 min-w-0 transition-all duration-300">
            <div class="min-h-screen flex flex-col">
                <x-admin.header />

                <div class="p-responsive flex-1">
                    @php
                        $hasSubNav = request()->routeIs('admin.school-profile.*', 'admin.news.*', 'admin.gallery.*', 'admin.programs.*', 'admin.contact-inquiries.*', 'admin.report-card.*', 'admin.assessments.attendance', 'admin.assessments.extracurricular', 'financial.*');
                    @endphp

                    @if ($hasSubNav)
                        {{-- Sticky pill nav: uses negative margin to bleed past p-responsive padding --}}
                        <div class="sticky top-0 z-10 -mt-4 md:-mt-8 lg:-mt-12 -mx-4 md:-mx-8 lg:-mx-12 px-4 md:px-8 lg:px-12 py-3 hidden lg:block">
                            <x-admin.konten-web-nav />
                            <x-admin.report-card-nav />
                            <x-admin.keuangan-nav />
                        </div>

                        {{-- Mobile: components output their own fixed bottom nav --}}
                        <div class="lg:hidden">
                            <x-admin.konten-web-nav />
                            <x-admin.report-card-nav />
                            <x-admin.keuangan-nav />
                        </div>
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
