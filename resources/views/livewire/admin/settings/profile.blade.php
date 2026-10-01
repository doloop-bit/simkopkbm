<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component {
    public string $name = '';
    public string $email = '';
    public string $phone = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->phone = Auth::user()->phone ?? '';
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id)
            ],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }
    // ... existing verification methods ...
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-admin.settings.layout :heading="__('Profile')" :subheading="__('Update your name, email, and phone number')">
        @if(auth()->user()?->isSiswa())
            @php
                $studentUser = auth()->user();
                $studentProfile = $studentUser->studentProfile ?? $studentUser->latestProfile?->profileable;
                $classroom = $studentProfile?->classroom;
            @endphp
            @if($studentProfile)
                <div class="mb-6 p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-2 text-xs sm:text-sm">
                    <div class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                        <x-ui.icon name="o-academic-cap" class="w-4 h-4 text-emerald-600" />
                        {{ __('Informasi Akademik Siswa') }}
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-slate-600 dark:text-slate-400">
                        <div>NIS: <strong class="text-slate-900 dark:text-white">{{ $studentProfile->nis ?? '-' }}</strong></div>
                        <div>NISN: <strong class="text-slate-900 dark:text-white">{{ $studentProfile->nisn ?? '-' }}</strong></div>
                        <div>Kelas: <strong class="text-slate-900 dark:text-white">{{ $classroom?->name ?? '-' }}</strong></div>
                        <div>Status: <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ __('Siswa Aktif Daring') }}</span></div>
                    </div>
                </div>
            @endif
        @endif

        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <x-ui.input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />
            
            <x-ui.input wire:model="phone" :label="__('Phone Number (WhatsApp)')" type="tel" placeholder="08xxxxxxxx" />

            <div>
                <x-ui.input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail &&! auth()->user()->hasVerifiedEmail())
                    <div class="mt-4">
                        <p class="text-sm opacity-70 italic">
                            {{ __('Your email address is unverified.') }}

                            <button type="button" class="text-primary hover:underline text-sm font-medium" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </button>
                        </p>

                        @if (session('status') === 'verification-link-sent')
                            <p class="mt-2 text-sm font-medium text-emerald-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <x-ui.button :label="__('Save')" type="submit" class="btn-primary" spinner="updateProfileInformation" data-test="update-profile-button" />

                <x-admin.action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-admin.action-message>
            </div>
        </form>

        @if(!auth()->user()?->isSiswa())
            <livewire:admin.settings.delete-user-form />
        @endif
    </x-admin.settings.layout>
</section>
