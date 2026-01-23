<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['auth', 'verified'])->group(function () {
    Volt::route('admin/report-card/create', 'admin.report-card.create')
        ->name('admin.report-card.create');
    Volt::route('admin/report-card/test', 'admin.report-card.test-generator')
        ->name('admin.report-card.test');
});
