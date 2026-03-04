<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->group(function () {
    Route::livewire('/pesan-kontak', 'admin.web-content.contact-inquiries.index')->name('admin.contact-inquiries.index');
});
