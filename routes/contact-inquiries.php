<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->group(function () {
    Volt::route('/pesan-kontak', 'admin.contact-inquiries.index')->name('admin.contact-inquiries.index');
});
