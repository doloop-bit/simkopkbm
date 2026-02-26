<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->group(function () {
    Route::livewire('/students', 'admin.data-master.students.index')->name('students.index');
    Route::livewire('/students/class-placements', 'admin.academic.class-placement')->name('students.class-placement');
});
