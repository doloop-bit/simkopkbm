<?php

use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

new class extends Component {
    #[Locked]
    public bool $twoFactorEnabled;

    #[Locked]
    public bool $requiresConfirmation;

    #[Locked]
    public string $qrCodeSvg = '';

    #[Locked]
    public string $manualSetupKey = '';

    public bool $showModal = false;

    public bool $showVerificationStep = false;

    #[Validate('required|string|size:6', onUpdate: false)]
    public string $code = '';

    /**
     * Mount the component.
     */
    public function mount(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        abort_unless(Features::enabled(Features::twoFactorAuthentication()), Response::HTTP_FORBIDDEN);

        if (Fortify::confirmsTwoFactorAuthentication() && is_null(auth()->user()->two_factor_confirmed_at)) {
            $disableTwoFactorAuthentication(auth()->user());
        }

        $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
        $this->requiresConfirmation = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
    }

    /**
     * Enable two-factor authentication for the user.
     */
    public function enable(EnableTwoFactorAuthentication $enableTwoFactorAuthentication): void
    {
        $enableTwoFactorAuthentication(auth()->user());

        if (! $this->requiresConfirmation) {
            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
        }

        $this->loadSetupData();

        $this->showModal = true;
    }

    /**
     * Load the two-factor authentication setup data for the user.
     */
    private function loadSetupData(): void
    {
        $user = auth()->user();

        try {
            $this->qrCodeSvg = $user?->twoFactorQrCodeSvg();
            $this->manualSetupKey = decrypt($user->two_factor_secret);
        } catch (Exception) {
            $this->addError('setupData', 'Failed to fetch setup data.');

            $this->reset('qrCodeSvg', 'manualSetupKey');
        }
    }

    /**
     * Show the two-factor verification step if necessary.
     */
    public function showVerificationIfNecessary(): void
    {
        if ($this->requiresConfirmation) {
            $this->showVerificationStep = true;

            $this->resetErrorBag();

            return;
        }

        $this->closeModal();
    }

    /**
     * Confirm two-factor authentication for the user.
     */
    public function confirmTwoFactor(ConfirmTwoFactorAuthentication $confirmTwoFactorAuthentication): void
    {
        $this->validate();

        $confirmTwoFactorAuthentication(auth()->user(), $this->code);

        $this->closeModal();

        $this->twoFactorEnabled = true;
    }

    /**
     * Reset two-factor verification state.
     */
    public function resetVerification(): void
    {
        $this->reset('code', 'showVerificationStep');

        $this->resetErrorBag();
    }

    /**
     * Disable two-factor authentication for the user.
     */
    public function disable(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $disableTwoFactorAuthentication(auth()->user());

        $this->twoFactorEnabled = false;
    }

    /**
     * Close the two-factor authentication modal.
     */
    public function closeModal(): void
    {
        $this->reset(
            'code',
            'manualSetupKey',
            'qrCodeSvg',
            'showModal',
            'showVerificationStep',
        );

        $this->resetErrorBag();

        if (! $this->requiresConfirmation) {
            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
        }
    }

    /**
     * Get the current modal configuration state.
     */
    public function getModalConfigProperty(): array
    {
        if ($this->twoFactorEnabled) {
            return [
                'title' => __('Two-Factor Authentication Enabled'),
                'description' => __('Two-factor authentication is now enabled. Scan the QR code or enter the setup key in your authenticator app.'),
                'buttonText' => __('Close'),
            ];
        }

        if ($this->showVerificationStep) {
            return [
                'title' => __('Verify Authentication Code'),
                'description' => __('Enter the 6-digit code from your authenticator app.'),
                'buttonText' => __('Continue'),
            ];
        }

        return [
            'title' => __('Enable Two-Factor Authentication'),
            'description' => __('To finish enabling two-factor authentication, scan the QR code or enter the setup key in your authenticator app.'),
            'buttonText' => __('Continue'),
        ];
    }
} ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-admin.settings.layout
        :heading="__('Two Factor Authentication')"
        :subheading="__('Manage your two-factor authentication settings')"
    >
        <div class="flex flex-col w-full mx-auto space-y-6 text-sm" wire:cloak>
            @if ($twoFactorEnabled)
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <x-ui.badge :label="__('Enabled')" class="bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400" />
                    </div>

                    <p class="opacity-70">
                        {{ __('With two-factor authentication enabled, you will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.') }}
                    </p>

                    <livewire:admin.settings.two-factor.recovery-codes :$requiresConfirmation/>

                    <div class="flex justify-start">
                        <x-ui.button
                            :label="__('Disable 2FA')"
                            class="bg-red-600 text-white shadow-lg shadow-red-600/20 hover:brightness-110"
                            icon="o-shield-exclamation"
                            wire:click="disable"
                            spinner="disable"
                        />
                    </div>
                </div>
            @else
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <x-ui.badge :label="__('Disabled')" class="bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400" />
                    </div>

                    <p class="opacity-60">
                        {{ __('When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.') }}
                    </p>

                    <x-ui.button
                        :label="__('Enable 2FA')"
                        class="btn-primary"
                        icon="o-shield-check"
                        wire:click="enable"
                        spinner="enable"
                    />
                </div>
            @endif
        </div>
    </x-admin.settings.layout>

    <x-ui.modal wire:model="showModal" persistent>
        <div class="space-y-6">
            <div class="flex flex-col items-center space-y-4">
                <div class="p-0.5 w-auto rounded-full border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm">
                    <div class="p-2.5 rounded-full border border-slate-300 dark:border-slate-600 overflow-hidden bg-slate-50 dark:bg-slate-900 relative">
                        <div class="flex items-stretch absolute inset-0 w-full h-full divide-x [&>div]:flex-1 divide-slate-200 dark:divide-slate-700 justify-around opacity-50">
                            @for ($i = 1; $i <= 5; $i++)
                                <div></div>
                            @endfor
                        </div>

                        <div class="flex flex-col items-stretch absolute w-full h-full divide-y [&>div]:flex-1 inset-0 divide-slate-200 dark:divide-slate-700 justify-around opacity-50">
                            @for ($i = 1; $i <= 5; $i++)
                                <div></div>
                            @endfor
                        </div>

                        <x-ui.icon name="o-qr-code" class="w-8 h-8 relative z-20 text-primary"/>
                    </div>
                </div>

                <div class="space-y-2 text-center">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">{{ $this->modalConfig['title'] }}</h3>
                    <p class="text-sm opacity-70 text-slate-600 dark:text-slate-400">{{ $this->modalConfig['description'] }}</p>
                </div>
            </div>

            @if ($showVerificationStep)
                <div class="space-y-6">
                    <div class="flex flex-col items-center space-y-3 justify-center text-center">
                        <x-ui.input
                            wire:model="code"
                            name="code"
                            placeholder="000000"
                            class="text-center text-2xl tracking-[1em] font-mono w-48"
                            maxlength="6"
                            autofocus
                        />
                    </div>

                    <div class="flex items-center space-x-3">
                        <x-ui.button
                            :label="__('Back')"
                            class="flex-1"
                            ghost
                            wire:click="resetVerification"
                        />

                        <x-ui.button
                            :label="__('Confirm')"
                            class="btn-primary flex-1"
                            wire:click="confirmTwoFactor"
                            x-bind:disabled="$wire.code.length < 6"
                            spinner="confirmTwoFactor"
                        />
                    </div>
                </div>
            @else
                @error('setupData')
                    <x-ui.alert :title="$message" icon="o-x-circle" class="bg-red-50 text-red-800 border-red-100" />
                @enderror

                <div class="flex justify-center">
                    <div class="relative w-64 overflow-hidden border rounded-lg border-slate-300 dark:border-slate-600 aspect-square">
                        @empty($qrCodeSvg)
                            <div class="absolute inset-0 flex items-center justify-center bg-slate-100 dark:bg-slate-800 animate-pulse">
                                <span class="w-8 h-8 border-4 border-primary border-t-transparent rounded-full animate-spin"></span>
                            </div>
                        @else
                            <div class="flex items-center justify-center h-full p-4 bg-white">
                                {!! $qrCodeSvg !!}
                            </div>
                        @endempty
                    </div>
                </div>

                <div>
                    <x-ui.button
                        :disabled="$errors->has('setupData')"
                        class="btn-primary w-full"
                        wire:click="showVerificationIfNecessary"
                        :label="$this->modalConfig['buttonText']"
                        spinner="showVerificationIfNecessary"
                    />
                </div>

                <div class="space-y-4">
                    <div class="relative flex items-center justify-center w-full">
                        <div class="absolute inset-0 w-full h-px top-1/2 bg-slate-300 dark:bg-slate-700"></div>
                        <span class="relative px-2 text-xs bg-white dark:bg-slate-800 text-slate-500">
                            {{ __('or, enter the code manually') }}
                        </span>
                    </div>

                    <div
                        class="flex items-center space-x-2"
                        x-data="{
                            copied: false,
                            async copy() {
                                try {
                                    await navigator.clipboard.writeText('{{ $manualSetupKey }}');
                                    this.copied = true;
                                    setTimeout(() => this.copied = false, 1500);
                                } catch (e) {
                                    console.warn('Could not copy to clipboard');
                                }
                            }
                        }"
                    >
                        <div class="flex items-stretch w-full border rounded-xl border-slate-300 dark:border-slate-600 overflow-hidden">
                            @empty($manualSetupKey)
                                <div class="flex items-center justify-center w-full p-3 bg-slate-100 dark:bg-slate-800">
                                    <span class="w-4 h-4 border-2 border-primary border-t-transparent rounded-full animate-spin"></span>
                                </div>
                            @else
                                <input
                                    type="text"
                                    readonly
                                    value="{{ $manualSetupKey }}"
                                    class="w-full p-3 bg-transparent outline-none text-sm font-mono text-slate-900 dark:text-white"
                                />

                                <button
                                    type="button"
                                    @click="copy()"
                                    class="px-4 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors border-l border-slate-300 dark:border-slate-600"
                                >
                                    <x-ui.icon x-show="!copied" name="o-document-duplicate" class="w-4 h-4" />
                                    <x-ui.icon x-show="copied" name="o-check" class="w-4 h-4 text-emerald-600" />
                                </button>
                            @endempty
                        </div>
                    </div>
                </div>
            @endif
        </div>
        
        <x-slot:actions>
            <x-ui.button :label="__('Close')" ghost wire:click="closeModal" />
        </x-slot:actions>
    </x-ui.modal>
</section>
