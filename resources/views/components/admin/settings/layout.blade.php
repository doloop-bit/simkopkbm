<div class="flex items-start max-md:flex-col gap-10">
    <div class="w-full md:w-[220px]">
        <nav class="flex flex-col gap-1">
            <a href="{{ route('profile.edit') }}" wire:navigate class="px-4 py-2 rounded-lg text-sm transition-colors {{ request()->routeIs('profile.edit') ? 'bg-primary text-primary-content font-bold' : 'hover:bg-base-200' }}">
                {{ __('Profile') }}
            </a>
            <a href="{{ route('user-password.edit') }}" wire:navigate class="px-4 py-2 rounded-lg text-sm transition-colors {{ request()->routeIs('user-password.edit') ? 'bg-primary text-primary-content font-bold' : 'hover:bg-base-200' }}">
                {{ __('Password') }}
            </a>
            @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                <a href="{{ route('two-factor.show') }}" wire:navigate class="px-4 py-2 rounded-lg text-sm transition-colors {{ request()->routeIs('two-factor.show') ? 'bg-primary text-primary-content font-bold' : 'hover:bg-base-200' }}">
                    {{ __('Two-Factor Auth') }}
                </a>
            @endif
            <a href="{{ route('appearance.edit') }}" wire:navigate class="px-4 py-2 rounded-lg text-sm transition-colors {{ request()->routeIs('appearance.edit') ? 'bg-primary text-primary-content font-bold' : 'hover:bg-base-200' }}">
                {{ __('Appearance') }}
            </a>
        </nav>
    </div>

    <div class="divider md:hidden"></div>

    <div class="flex-1 self-stretch max-md:pt-6">
        <h2 class="text-2xl font-black tracking-tight">{{ $heading ?? '' }}</h2>
        <p class="text-sm opacity-60">{{ $subheading ?? '' }}</p>

        <div class="mt-5 w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
