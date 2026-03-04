<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->group(function () {
    Route::livewire('/academic/years', 'admin.academic.academic-years')->name('academic.years');
    Route::livewire('/academic/levels', 'admin.academic.levels')->name('academic.levels');
    Route::livewire('/academic/classrooms', 'admin.academic.classrooms')->name('academic.classrooms');
    Route::livewire('/academic/subjects', 'admin.academic.subjects')->name('academic.subjects');
    Route::livewire('/academic/assignments', 'admin.academic.teacher-assignments')->name('academic.assignments');
    Route::livewire('/academic/attendance', 'admin.academic.attendance')->name('academic.attendance');
    Route::livewire('/academic/grades', 'admin.academic.grades')->name('academic.grades');
    Route::livewire('/academic/extracurriculars', 'admin.academic.extracurriculars')->name('academic.extracurriculars');
});
