<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin/data-master')->group(function () {
    Route::livewire('users', 'admin.data-master.users')->name('users.index');
});
