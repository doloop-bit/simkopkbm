<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    // Competency Assessment (SD/SMP/SMA)
    Volt::route('/assessments/competency', 'admin.assessments.competency-assessment')
        ->name('assessments.competency');
    
    // P5 Assessment (Projek Penguatan Profil Pelajar Pancasila)
    Volt::route('/assessments/p5', 'admin.assessments.p5-assessment')
        ->name('assessments.p5');
    
    // Extracurricular Assessment
    Volt::route('/assessments/extracurricular', 'admin.assessments.extracurricular-assessment')
        ->name('assessments.extracurricular');
    
    // PAUD Developmental Assessment
    Volt::route('/assessments/paud', 'admin.assessments.paud-assessment')
        ->name('assessments.paud');

    // Report Attendance Summary (Sick, Permit, Alpha)
    Volt::route('/assessments/attendance', 'admin.assessments.attendance-input')
        ->name('assessments.attendance');
});
