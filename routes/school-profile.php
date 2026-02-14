<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->group(function () {
    Volt::route('/profil-sekolah', 'admin.web-content.school-profile.edit')->name('admin.school-profile.edit');
    Volt::route('/profil-sekolah/struktur-organisasi', 'admin.web-content.school-profile.staff-members')->name('admin.school-profile.staff-members');
    Volt::route('/profil-sekolah/fasilitas', 'admin.web-content.school-profile.facilities')->name('admin.school-profile.facilities');
});
