<?php

use Illuminate\Support\Facades\Route;

// Public registration moved to public.php (Svelte V2 and Livewire V1)

// Admin registration management
Route::middleware(['auth', 'verified', 'role:admin,kepsek'])->prefix('admin')->group(function () {
    Route::livewire('/registrations', 'admin.registrations.index')->name('admin.registrations.index');
});
