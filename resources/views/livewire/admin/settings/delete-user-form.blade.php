<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component {
    public string $password = '';
    public bool $confirmingDeletion = false;

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="mt-10 space-y-6">
    <div class="relative mb-5">
        <h2 class="text-lg font-bold">{{ __('Delete account') }}</h2>
        <p class="text-sm opacity-70">{{ __('Delete your account and all of its resources') }}</p>
    </div>

    <x-button label="{{ __('Delete account') }}" class="btn-error" click="$set('confirmingDeletion', true)" data-test="delete-user-button" />

    <x-modal wire:model="confirmingDeletion" class="backdrop-blur">
        <x-header title="{{ __('Are you sure you want to delete your account?') }}" separator />
        
        <p class="text-sm opacity-70 mb-6">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
        </p>

        <form method="POST" wire:submit="deleteUser" class="space-y-6 text-left">
            <x-input wire:model="password" :label="__('Password')" type="password" required />

            <div class="flex justify-end gap-2">
                <x-button label="{{ __('Cancel') }}" click="$set('confirmingDeletion', false)" />

                <x-button label="{{ __('Delete account') }}" type="submit" class="btn-error" data-test="confirm-delete-user-button" spinner="deleteUser" />
            </div>
        </form>
    </x-modal>
</section>
