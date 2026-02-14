<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->group(function () {
    Volt::route('/berita', 'admin.web-content.news.index')->name('admin.news.index');
    Volt::route('/berita/create', 'admin.web-content.news.form')->name('admin.news.create');
    Volt::route('/berita/{id}/edit', 'admin.web-content.news.form')->name('admin.news.edit');
});
