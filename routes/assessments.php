<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    // Competency Assessment (PAUD)
    Volt::route('/assessments/competency', 'admin.report-card.paud.competency-assessment')
        ->name('assessments.competency');

    // Extracurricular Assessment
    Volt::route('/assessments/extracurricular', 'admin.assessments.extracurricular')
        ->name('assessments.extracurricular');

    // Report Attendance Summary (Sick, Permit, Alpha)
    Volt::route('/assessments/attendance', 'admin.assessments.attendance')
        ->name('assessments.attendance');
});
