<x-layouts.auth>
    <div class="mt-4 flex flex-col gap-6">
        <p class="text-center opacity-70">
            {{ __('Please verify your email address by clicking on the link we just emailed to you.') }}
        </p>

        @if (session('status') == 'verification-link-sent')
            <p class="text-center font-medium text-emerald-600">
                {{ __('A new verification link has been sent to the email address you provided during registration.') }}
            </p>
        @endif

        <div class="flex flex-col items-center justify-between space-y-3">
            <form method="POST" action="{{ route('verification.send') }}" class="w-full">
                @csrf
                <x-ui.button type="submit" class="btn-primary w-full" :label="__('Resend verification email')" />
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-ui.button type="submit" ghost sm data-test="logout-button" :label="__('Log out')" />
            </form>
        </div>
    </div>
</x-layouts.auth>
