<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Teacher Routes
|--------------------------------------------------------------------------
|
| Routes accessible to users with role='guru'
| All routes are filtered to show only data for teacher's assigned classrooms/subjects
|
*/

Route::middleware(['auth', 'verified', 'role:guru'])->prefix('teacher')->name('teacher.')->group(function () {

    // Dashboard
    Route::livewire('/dashboard', 'teacher.dashboard')->name('dashboard');

    // Students - filtered by assigned classrooms
    Route::livewire('/students', 'teacher.data-master.students.index')->name('students.index');

    // Report Card & Assessments
    Route::livewire('/report-cards', 'teacher.report-card.index')->name('report-cards');

    // Assessments - filtered by assigned classrooms
    Route::livewire('/assessments/grading', 'teacher.assessments.grading')
        ->name('assessments.grading');
    Route::livewire('/assessments/extracurricular', 'teacher.assessments.extracurricular')
        ->name('assessments.extracurricular');
    Route::livewire('/assessments/paud', 'teacher.report-card.paud.developmental')->name('assessments.paud');
    Route::livewire('/assessments/attendance', 'teacher.assessments.attendance')->name('assessments.attendance'); // Rekap Rapor
    Route::livewire('/attendance/daily', 'teacher.attendance.daily')->name('attendance.daily'); // Presensi Harian
});
