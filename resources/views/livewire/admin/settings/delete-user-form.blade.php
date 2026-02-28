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
        <h2 class="text-lg font-bold text-red-600 dark:text-red-400">{{ __('Delete account') }}</h2>
        <p class="text-sm opacity-70 text-slate-600 dark:text-slate-400">{{ __('Delete your account and all of its resources') }}</p>
    </div>

    <x-ui.button :label="__('Delete account')" class="bg-red-600 text-white shadow-lg shadow-red-600/20 hover:brightness-110" wire:click="$set('confirmingDeletion', true)" data-test="delete-user-button" />

    <x-ui.modal wire:model="confirmingDeletion">
        <x-ui.header :title="__('Are you sure you want to delete your account?')" separator />
        
        <p class="text-sm opacity-70 mb-6 text-slate-600 dark:text-slate-400">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
        </p>

        <form method="POST" wire:submit="deleteUser" class="space-y-6 text-left">
            <x-ui.input wire:model="password" :label="__('Password')" type="password" required />

            <div class="flex justify-end gap-2 text-right">
                <x-ui.button :label="__('Cancel')" ghost wire:click="$set('confirmingDeletion', false)" />

                <x-ui.button :label="__('Delete account')" type="submit" class="bg-red-600 text-white shadow-lg shadow-red-600/20 hover:brightness-110" data-test="confirm-delete-user-button" spinner="deleteUser" />
            </div>
        </form>
    </x-ui.modal>
</section>
