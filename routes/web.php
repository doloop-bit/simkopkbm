<?php

use Illuminate\Support\Facades\Route;

Route::view('admin/dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::livewire('/select-role', 'auth.select-role')
    ->middleware(['auth', 'verified'])
    ->name('select-role');

require __DIR__.'/settings.php';
require __DIR__.'/academic.php';
require __DIR__.'/students.php';
require __DIR__.'/financial.php';
require __DIR__.'/ptk.php';
require __DIR__.'/school-profile.php';
require __DIR__.'/news.php';
require __DIR__.'/gallery.php';
require __DIR__.'/programs.php';
require __DIR__.'/contact-inquiries.php';
require __DIR__.'/report-card.php';
require __DIR__.'/assessments.php';
require __DIR__.'/teacher.php';
require __DIR__.'/users.php';
require __DIR__.'/registrations.php';
require __DIR__.'/calendar.php';

// Public website routes (placed last because it contains a catch-all root-level /{slug} route)
require __DIR__.'/public.php';

Route::livewire('/test-modal', 'test-modal')
    ->middleware(['auth', 'verified']);

Route::get('/test-at', function () {
    return view('test-at');
})->middleware(['auth', 'verified']);

Route::livewire('/dev/tools', 'dev-tools')
    ->name('dev.tools');
