<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    // Competency Assessment (PAUD)
    Route::livewire('/assessments/competency', 'admin.report-card.paud.competency-assessment')
        ->name('assessments.competency');

    // Extracurricular Assessment
    Route::livewire('/assessments/extracurricular', 'admin.assessments.extracurricular')
        ->name('assessments.extracurricular');

    // Report Attendance Summary (Sick, Permit, Alpha)
    Route::livewire('/assessments/attendance', 'admin.assessments.attendance')
        ->name('assessments.attendance');
});
