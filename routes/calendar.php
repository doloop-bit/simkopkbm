<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::livewire('/calendar', 'admin.academic.calendar')->name('calendar.index');
    });
});
