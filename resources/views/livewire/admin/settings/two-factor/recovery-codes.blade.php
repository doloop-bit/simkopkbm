<?php

use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component {
    #[Locked]
    public array $recoveryCodes = [];

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->loadRecoveryCodes();
    }

    /**
     * Generate new recovery codes for the user.
     */
    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generateNewRecoveryCodes): void
    {
        $generateNewRecoveryCodes(auth()->user());

        $this->loadRecoveryCodes();
    }

    /**
     * Load the recovery codes for the user.
     */
    private function loadRecoveryCodes(): void
    {
        $user = auth()->user();

        if ($user->hasEnabledTwoFactorAuthentication() && $user->two_factor_recovery_codes) {
            try {
                $this->recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
            } catch (Exception) {
                $this->addError('recoveryCodes', 'Failed to load recovery codes');

                $this->recoveryCodes = [];
            }
        }
    }
}; ?>

<div
    class="py-6 space-y-6 border border-base-300 shadow-sm rounded-xl"
    wire:cloak
    x-data="{ showRecoveryCodes: false }"
>
    <div class="px-6 space-y-2 text-left">
        <div class="flex items-center gap-2">
            <x-icon name="o-lock-closed" class="w-4 h-4"/>
            <h3 class="text-lg font-bold">{{ __('2FA Recovery Codes') }}</h3>
        </div>
        <p class="text-sm opacity-70">
            {{ __('Recovery codes let you regain access if you lose your 2FA device. Store them in a secure password manager.') }}
        </p>
    </div>

    <div class="px-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <x-button
                x-show="!showRecoveryCodes"
                icon="o-eye"
                class="btn-primary"
                @click="showRecoveryCodes = true;"
                label="{{ __('View Recovery Codes') }}"
            />

            <x-button
                x-show="showRecoveryCodes"
                icon="o-eye-slash"
                class="btn-primary"
                @click="showRecoveryCodes = false"
                label="{{ __('Hide Recovery Codes') }}"
            />

            @if (filled($recoveryCodes))
                <x-button
                    x-show="showRecoveryCodes"
                    icon="o-arrow-path"
                    wire:click="regenerateRecoveryCodes"
                    label="{{ __('Regenerate Codes') }}"
                    spinner="regenerateRecoveryCodes"
                />
            @endif
        </div>

        <div
            x-show="showRecoveryCodes"
            x-transition
            id="recovery-codes-section"
            class="relative overflow-hidden"
        >
            <div class="mt-3 space-y-3">
                @error('recoveryCodes')
                    <x-alert title="{{ $message }}" icon="o-x-circle" class="alert-error" />
                @enderror

                @if (filled($recoveryCodes))
                    <div
                        class="grid gap-1 p-4 font-mono text-sm rounded-lg bg-base-200"
                    >
                        @foreach($recoveryCodes as $code)
                            <div
                                wire:loading.class="opacity-50 animate-pulse"
                            >
                                {{ $code }}
                            </div>
                        @endforeach
                    </div>
                    <p class="text-xs opacity-60">
                        {{ __('Each recovery code can be used once to access your account and will be removed after use. If you need more, click Regenerate Codes above.') }}
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
