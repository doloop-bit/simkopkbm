<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->group(function () {
    Route::livewire('/berita', 'admin.web-content.news.index')->name('admin.news.index');
    Route::livewire('/berita/create', 'admin.web-content.news.form')->name('admin.news.create');
    Route::livewire('/berita/{id}/edit', 'admin.web-content.news.form')->name('admin.news.edit');
});
