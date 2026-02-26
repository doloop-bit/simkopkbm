<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    // Redirect index to grading page (default)
    Route::get('admin/report-card', function () {
        return redirect()->route('admin.report-card.grading');
    })->name('admin.report-card.index');

    Route::livewire('admin/report-card/create', 'admin.report-card.create')
        ->name('admin.report-card.create');
    Route::livewire('admin/report-card/grading', 'admin.assessments.grading')
        ->name('admin.report-card.grading');
});
