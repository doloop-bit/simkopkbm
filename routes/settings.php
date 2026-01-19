<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'admin.settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'admin.settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'admin.settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'admin.settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});
