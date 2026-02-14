<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

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
    Volt::route('/dashboard', 'teacher.dashboard')->name('dashboard');

    // Students - filtered by assigned classrooms
    Volt::route('/students', 'teacher.data-master.students.index')->name('students.index');

    // Academic
    Volt::route('/grades', 'teacher.assessments.grading')
        ->name('academic.grades');

    // Report Card & Assessments
    Volt::route('/report-cards', 'teacher.report-card.index')->name('report-cards');

    // Assessments - filtered by assigned classrooms
    Volt::route('/assessments/competency', 'teacher.assessments.grading')
        ->name('assessments.competency');
    Volt::route('/assessments/extracurricular', 'teacher.assessments.extracurricular')
        ->name('assessments.extracurricular');
    Volt::route('/assessments/paud', 'teacher.report-card.paud.developmental')->name('assessments.paud');
    Volt::route('/assessments/attendance', 'teacher.assessments.attendance')->name('assessments.attendance');
});
