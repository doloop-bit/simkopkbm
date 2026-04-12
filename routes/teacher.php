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

    // Data Master
    Route::livewire('/profile', 'teacher.profile')->name('profile');
    Route::livewire('/students', 'teacher.data-master.students.index')->name('students.index');

    // Academic
    Route::livewire('/subjects', 'shared.academic.subjects')->name('academic.subjects');
    Route::livewire('/diniyah-subjects', 'shared.academic.diniyah-subjects')->name('academic.diniyah-subjects');
    Route::livewire('/extracurriculars', 'shared.academic.extracurriculars')->name('academic.extracurriculars');

    // Report Card & Assessments
    Route::livewire('/report-cards', 'teacher.report-card.index')->name('report-cards');

    // Assessments - filtered by assigned classrooms
    Route::livewire('/assessments/grading', 'shared.assessments.grading')
        ->name('assessments.grading');
    Route::livewire('/assessments/diniyah', 'teacher.assessments.diniyah-grading')
        ->name('assessments.diniyah');
    Route::livewire('/assessments/extracurricular', 'shared.assessments.extracurricular')
        ->name('assessments.extracurricular');
    Route::livewire('/assessments/paud', 'teacher.report-card.paud.developmental')->name('assessments.paud');
    Route::livewire('/assessments/attendance', 'shared.assessments.attendance')->name('assessments.attendance'); // Rekap Rapor
    Route::livewire('/attendance/daily', 'shared.attendance.daily')->name('attendance.daily'); // Presensi Harian
});
