<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['auth', 'verified'])->group(function () {
    // Redirect index to grading page (default)
    Route::get('admin/report-card', function () {
        return redirect()->route('admin.report-card.grading');
    })->name('admin.report-card.index');

    Volt::route('admin/report-card/create', 'admin.report-card.create')
        ->name('admin.report-card.create');
    Volt::route('admin/report-card/grading', 'admin.assessments.grading')
        ->name('admin.report-card.grading');
});
