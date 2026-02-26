<x-nav sticky full-width class="lg:hidden">
    <x-slot:brand>
        <label for="main-drawer" class="lg:hidden mr-3 cursor-pointer">
            <x-icon name="o-bars-3" class="size-6" />
        </label>
        <x-global.app-logo-icon class="size-6 mr-2 fill-primary" />
        <span class="font-bold">{{ config('app.name') }}</span>
    </x-slot:brand>

    <x-slot:actions>
        <x-dropdown icon="o-user-circle" class="btn-ghost btn-sm" right transition>
             <div class="px-3 py-2 border-b border-base-200">
                <div class="font-bold">{{ auth()->user()->name }}</div>
                <div class="text-xs opacity-60">{{ auth()->user()->email }}</div>
            </div>

            <x-menu-item title="Settings" icon="o-cog-6-tooth" :link="route('profile.edit')" />
            
            <x-menu-separator />
            
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-menu-item title="Log Out" icon="o-power" onclick="this.closest('form').submit(); return false;" />
            </form>
        </x-dropdown>
    </x-slot:actions>
</x-nav>
