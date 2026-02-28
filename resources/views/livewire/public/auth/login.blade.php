<x-layouts.auth>
    <div class="flex flex-col gap-6">
        <x-layouts.auth.auth-header :title="__('Masuk ke Akun Anda')" :description="__('Masukkan Email dan Password di bawah untuk login')" />

        <!-- Session Status -->
        <x-layouts.auth.auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <div>
                <x-ui.input
                    name="email"
                    :label="__('Email address')"
                    :value="old('email')"
                    type="email"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="email@example.com"
                />
            </div>

            <!-- Password -->
            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Password') }}</label>
                    @if (Route::has('password.request'))
                        <a class="text-xs text-primary hover:underline opacity-70" href="{{ route('password.request') }}" wire:navigate>
                            {{ __('Lupa password?') }}
                        </a>
                    @endif
                </div>
                <x-ui.input
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Password')"
                />
            </div>

            <!-- Remember Me -->
            <x-ui.checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <x-ui.button type="submit" class="btn-primary w-full" data-test="login-button" :label="__('Log in')" />
            </div>
        </form>

        @if (Route::has('register'))
            <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
                <span>{{ __('Don\'t have an account?') }}</span>
                <a href="{{ route('register') }}" class="text-primary font-semibold hover:underline" wire:navigate>{{ __('Sign up') }}</a>
            </div>
        @endif
    </div>
</x-layouts.auth>
