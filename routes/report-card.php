<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    // Redirect index to grading page (default)
    Route::get('admin/report-card', function () {
        return redirect()->route('admin.report-card.grading');
    })->name('admin.report-card.index');

    Route::livewire('admin/report-card/create', 'admin.report-card.create')
        ->middleware('role:admin')
        ->name('admin.report-card.create');

    Route::livewire('admin/report-card/grading', 'shared.assessments.grading')
        ->name('admin.report-card.grading');

    Route::livewire('admin/report-card/diniyah-grading', 'admin.report-card.diniyah-grading')
        ->name('admin.report-card.diniyah-grading');

    Route::livewire('admin/report-card/diniyah', 'admin.report-card.diniyah-create')
        ->middleware('role:admin')
        ->name('admin.report-card.diniyah');

    // PAUD Routes
    Route::livewire('admin/report-card/paud/master', 'admin.report-card.paud.paud-master-data')
        ->middleware('role:admin')
        ->name('admin.report-card.paud.master');

    Route::livewire('admin/report-card/paud/tp', 'admin.report-card.paud.paud-tp-management')
        ->name('admin.report-card.paud.tp');

    Route::livewire('admin/report-card/paud/grading', 'admin.report-card.paud.paud-tp-grading')
        ->name('admin.report-card.paud.grading');

    Route::livewire('admin/report-card/paud/generate', 'admin.report-card.paud.paud-report-create')
        ->middleware('role:admin')
        ->name('admin.report-card.paud.generate');
});
