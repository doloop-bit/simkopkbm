<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    Route::middleware(['auth', 'verified', 'role:admin,kepsek'])->group(function () {
        Route::livewire('/academic/years', 'admin.academic.academic-years')->name('academic.years');
        Route::livewire('/academic/levels', 'admin.academic.levels')->name('academic.levels');
        Route::livewire('/academic/classrooms', 'admin.academic.classrooms')->name('academic.classrooms');
        Route::livewire('/academic/assignments', 'admin.academic.teacher-assignments')->name('academic.assignments');
        Route::livewire('/academic/subjects', 'shared.academic.subjects')->name('academic.subjects');
        Route::livewire('/academic/diniyah-subjects', 'shared.academic.diniyah-subjects')->name('academic.diniyah-subjects');
        Route::livewire('/academic/attendance', 'shared.attendance.daily')->name('academic.attendance');
        Route::livewire('/academic/grades', 'admin.academic.grades')->name('academic.grades');
        Route::livewire('/academic/extracurriculars', 'shared.academic.extracurriculars')->name('academic.extracurriculars');
    });
});
