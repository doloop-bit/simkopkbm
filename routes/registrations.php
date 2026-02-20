<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// Public registration
Volt::route('/pendaftaran', 'public.register')->name('public.register');

// Admin registration management
Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->group(function () {
    Volt::route('/registrations', 'admin.registrations.index')->name('admin.registrations.index');
});
