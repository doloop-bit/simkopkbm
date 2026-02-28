<x-nav sticky full-width class="glass-nav px-4 lg:px-8">
    <x-slot:brand class="lg:hidden">
        <label for="main-drawer" class="lg:hidden mr-3 cursor-pointer">
            <x-icon name="o-bars-3" class="size-6 text-slate-500" />
        </label>
        <x-global.app-logo-icon class="size-6 mr-2 fill-primary" />
        <span class="font-bold text-slate-900 dark:text-white">{{ config('app.name') }}</span>
    </x-slot:brand>

    {{-- Desktop Breadcrumbs or Search could go here --}}
    <x-slot:brand class="hidden lg:flex items-center gap-2">
        <div class="text-sm font-medium text-slate-400">Admin</div>
        <x-icon name="o-chevron-right" class="size-3 text-slate-300" />
        <div class="text-sm font-semibold text-slate-900 dark:text-white">{{ $title ?? 'Dashboard' }}</div>
    </x-slot:brand>

    <x-slot:actions>
        <div class="flex items-center gap-3">
             <x-button icon="o-bell" class="btn-ghost btn-sm text-slate-500" />
             
             <x-dropdown right transition>
                <x-slot:label>
                    <x-button icon="o-user" class="btn-ghost btn-circle btn-sm" />
                </x-slot:label>

                <div class="px-5 py-4 w-64">
                    <div class="font-bold text-slate-900 dark:text-white">{{ auth()->user()->name }}</div>
                    <div class="text-xs text-slate-500">{{ auth()->user()->email }}</div>
                </div>

                <x-menu-separator />
                
                <x-menu-item title="Profile Settings" icon="o-cog-6-tooth" :link="route('profile.edit')" />
                
                <x-menu-separator />
                
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-menu-item title="Log Out" icon="o-power" class="text-error" onclick="this.closest('form').submit(); return false;" />
                </form>
            </x-dropdown>
        </div>
    </x-slot:actions>
</x-nav>
