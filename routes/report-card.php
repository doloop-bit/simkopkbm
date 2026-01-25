<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['auth', 'verified'])->group(function () {
    Volt::route('admin/report-card/create', 'admin.report-card.create')
        ->name('admin.report-card.create');
});
