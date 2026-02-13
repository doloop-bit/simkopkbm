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
    Volt::route('/students', 'teacher.students.index')->name('students.index');



    // Assessments - filtered by assigned classrooms
    Volt::route('/assessments/competency', 'teacher.assessments.competency')->name('assessments.competency');
    Volt::route('/assessments/p5', 'teacher.assessments.p5')->name('assessments.p5');
    Volt::route('/assessments/extracurricular', 'teacher.assessments.extracurricular')->name('assessments.extracurricular');
    Volt::route('/assessments/paud', 'teacher.assessments.paud')->name('assessments.paud');
    Volt::route('/assessments/attendance', 'teacher.assessments.attendance')->name('assessments.attendance');

    // Report Cards - read-only, filtered by assigned classrooms
    Volt::route('/report-cards', 'teacher.report-cards')->name('report-cards');
});
