<div x-data="{ open: false }" class="relative w-full">
    <button @click="open = !open" @click.outside="open = false" type="button" class="flex items-center gap-3 w-full px-2 py-2 rounded-xl hover:bg-slate-800/50 transition-colors cursor-pointer" :class="sidebarCollapsed ? 'justify-center' : ''">
        <x-ui.icon name="o-user-circle" class="w-6 h-6 text-slate-400 shrink-0" x-show="sidebarCollapsed" />
        <div class="flex-1 min-w-0 text-left" x-show="!sidebarCollapsed">
            <div class="text-sm font-semibold text-slate-200 truncate">{{ auth()->user()->name }}</div>
            <div class="text-xs text-slate-400 truncate">{{ auth()->user()->email }}</div>
        </div>
        <x-ui.icon name="o-chevron-up-down" class="w-4 h-4 text-slate-500 shrink-0" x-show="!sidebarCollapsed" />
    </button>

    {{-- Dropdown menu --}}
    <div x-show="open" x-cloak
         x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
         @click="open = false"
         class="absolute bottom-full left-0 min-w-48 mb-2 rounded-xl border border-slate-700 bg-slate-800 shadow-xl py-1 z-50 overflow-hidden"
         :class="sidebarCollapsed ? '' : 'right-0'">
        <a href="{{ route('profile.edit') }}" wire:navigate class="flex items-center gap-2 px-4 py-2 text-sm text-slate-300 hover:bg-slate-700 transition-colors">
            <x-ui.icon name="o-cog-6-tooth" class="w-4 h-4" />
            Settings
        </a>
        <div class="border-t border-slate-700 my-1"></div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex items-center gap-2 w-full px-4 py-2 text-sm text-red-400 hover:bg-slate-700 transition-colors">
                <x-ui.icon name="o-power" class="w-4 h-4" />
                Log Out
            </button>
        </form>
    </div>
</div>
