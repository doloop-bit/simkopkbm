<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Volt::route('/academic/years', 'admin.academic.academic-years')->name('academic.years');
    Volt::route('/academic/levels', 'admin.academic.levels')->name('academic.levels');
    Volt::route('/academic/classrooms', 'admin.academic.classrooms')->name('academic.classrooms');
    Volt::route('/academic/subjects', 'admin.academic.subjects')->name('academic.subjects');
    Volt::route('/academic/assignments', 'admin.academic.teacher-assignments')->name('academic.assignments');
    Volt::route('/academic/attendance', 'admin.academic.attendance')->name('academic.attendance');
    Volt::route('/academic/grades', 'admin.academic.grades')->name('academic.grades');
});
