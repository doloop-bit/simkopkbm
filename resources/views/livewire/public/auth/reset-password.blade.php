<x-layouts.auth>
    <div class="flex flex-col gap-6">
        <x-layouts.auth.auth-header :title="__('Reset password')" :description="__('Please enter your new password below')" />

        <!-- Session Status -->
        <x-layouts.auth.auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Token -->
            <input type="hidden" name="token" value="{{ request()->route('token') }}">

            <!-- Email Address -->
            <x-ui.input
                name="email"
                value="{{ request('email') }}"
                :label="__('Email')"
                type="email"
                required
                autocomplete="email"
            />

            <!-- Password -->
            <x-ui.input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Password')"
            />

            <!-- Confirm Password -->
            <x-ui.input
                name="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirm password')"
            />

            <div class="flex items-center justify-end">
                <x-ui.button type="submit" class="btn-primary w-full" data-test="reset-password-button" :label="__('Reset password')" />
            </div>
        </form>
    </div>
</x-layouts.auth>
