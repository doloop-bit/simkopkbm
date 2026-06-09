<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {

    // Extracurricular Assessment
    Route::livewire('/assessments/extracurricular', 'shared.assessments.extracurricular')
        ->name('assessments.extracurricular');

    // Report Attendance Summary (Sick, Permit, Alpha)
    Route::livewire('/assessments/attendance', 'shared.assessments.attendance')
        ->name('assessments.attendance');
});
