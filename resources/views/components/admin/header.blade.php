<header class="glass-nav px-4 lg:px-8">
    <div class="flex items-center justify-between py-3">
        {{-- Left: Mobile menu + Brand --}}
        <div class="flex items-center gap-3">
            {{-- Mobile hamburger --}}
            <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                <x-ui.icon name="o-bars-3" class="w-6 h-6" />
            </button>

            {{-- Desktop collapse --}}
            <button @click="sidebarCollapsed = !sidebarCollapsed; localStorage.setItem('sidebarCollapsed', sidebarCollapsed)" class="hidden lg:block p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                <x-ui.icon name="o-bars-3" class="w-6 h-6" x-bind:class="sidebarCollapsed ? 'rotate-180 transition-transform' : 'transition-transform'" />
            </button>

            {{-- Mobile brand --}}
            <div class="flex items-center gap-2 lg:hidden">
                <x-global.app-logo-icon class="size-6 fill-primary" />
                <span class="font-bold text-slate-900 dark:text-white">{{ config('app.name') }}</span>
            </div>

            {{-- Desktop breadcrumb --}}
            <div class="hidden lg:flex items-center gap-2">
                <div class="text-sm font-medium text-slate-400">Admin</div>
                <x-ui.icon name="o-chevron-right" class="w-3 h-3 text-slate-300" />
                <div class="text-sm font-semibold text-slate-900 dark:text-white">{{ $title ?? 'Dashboard' }}</div>
            </div>
        </div>

        {{-- Right: Actions --}}
        <div class="flex items-center gap-3">
            <button class="p-2 rounded-xl text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                <x-ui.icon name="o-bell" class="w-5 h-5" />
            </button>

            {{-- User dropdown --}}
            <x-ui.dropdown>
                <x-slot:trigger>
                    <button class="p-2 rounded-full text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <x-ui.icon name="o-user" class="w-5 h-5" />
                    </button>
                </x-slot:trigger>

                <x-slot:content>
                    <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700">
                        <div class="font-bold text-slate-900 dark:text-white">{{ auth()->user()->name }}</div>
                        <div class="text-xs text-slate-500">{{ auth()->user()->email }}</div>
                    </div>
                    <a href="{{ route('profile.edit') }}" wire:navigate class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <x-ui.icon name="o-cog-6-tooth" class="w-4 h-4" />
                        Profile Settings
                    </a>
                    <div class="border-t border-slate-200 dark:border-slate-700"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex items-center gap-2 w-full px-4 py-2 text-sm text-red-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <x-ui.icon name="o-power" class="w-4 h-4" />
                            Log Out
                        </button>
                    </form>
                </x-slot:content>
            </x-ui.dropdown>
        </div>
    </div>
</header>
