<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->group(function () {
    Route::livewire('/ptk', 'admin.data-master.ptk.index')->name('ptk.index');
});
