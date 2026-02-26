<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->group(function () {
    Route::livewire('/profil-sekolah', 'admin.web-content.school-profile.edit')->name('admin.school-profile.edit');
    Route::livewire('/profil-sekolah/struktur-organisasi', 'admin.web-content.school-profile.staff-members')->name('admin.school-profile.staff-members');
    Route::livewire('/profil-sekolah/fasilitas', 'admin.web-content.school-profile.facilities')->name('admin.school-profile.facilities');
});
