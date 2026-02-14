<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    // Competency Assessment (PAUD)
    Volt::route('/assessments/competency', 'admin.report-card.paud.competency-assessment')
        ->name('assessments.competency');

    // Extracurricular Assessment
    Volt::route('/assessments/extracurricular', 'admin.report-card.extracurricular-assessment')
        ->name('assessments.extracurricular');

    // Report Attendance Summary (Sick, Permit, Alpha)
    Volt::route('/assessments/attendance', 'admin.report-card.attendance-input')
        ->name('assessments.attendance');
});
