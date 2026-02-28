<x-layouts.auth>
    <div class="flex flex-col gap-6">
        <x-layouts.auth.auth-header :title="__('Forgot password')" :description="__('Enter your email to receive a password reset link')" />

        <!-- Session Status -->
        <x-layouts.auth.auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <x-ui.input
                name="email"
                :label="__('Email Address')"
                type="email"
                required
                autofocus
                placeholder="email@example.com"
            />

            <x-ui.button type="submit" class="btn-primary w-full" data-test="email-password-reset-link-button" :label="__('Email password reset link')" />
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-400">
            <span>{{ __('Or, return to') }}</span>
            <a href="{{ route('login') }}" class="text-primary font-semibold hover:underline" wire:navigate>{{ __('log in') }}</a>
        </div>
    </div>
</x-layouts.auth>
