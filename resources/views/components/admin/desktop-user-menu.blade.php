<x-dropdown right top transition class="w-full">
    <x-slot:label>
        <x-list-item :item="auth()->user()" value="name" sub-value="email" no-separator no-hover class="!p-0">
            <x-slot:actions>
                <x-icon name="o-chevron-up-down" class="size-4" />
            </x-slot:actions>
        </x-list-item>
    </x-slot:label>

    <x-menu-item title="Settings" icon="o-cog-6-tooth" :link="route('profile.edit')" />
    <x-menu-separator />
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <x-menu-item title="Log Out" icon="o-power" onclick="this.closest('form').submit(); return false;" />
    </form>
</x-dropdown>
