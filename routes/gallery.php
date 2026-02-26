<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->group(function () {
    Route::livewire('/galeri', 'admin.web-content.gallery.index')->name('admin.gallery.index');
});
