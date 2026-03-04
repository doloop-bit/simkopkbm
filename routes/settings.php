<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::middleware(['auth', 'verified'])->prefix('admin')->group(function () {
    Route::redirect('settings', 'admin/settings/profile');

    Route::livewire('settings/profile', 'admin.settings.profile')->name('profile.edit');
    Route::livewire('settings/password', 'admin.settings.password')->name('user-password.edit');
    Route::livewire('settings/appearance', 'admin.settings.appearance')->name('appearance.edit');

    Route::livewire('settings/two-factor', 'admin.settings.two-factor')
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
