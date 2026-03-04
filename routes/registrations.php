<?php

use Illuminate\Support\Facades\Route;

// Public registration
Route::livewire('/pendaftaran', 'public.register')->name('public.register');

// Admin registration management
Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->group(function () {
    Route::livewire('/registrations', 'admin.registrations.index')->name('admin.registrations.index');
});
