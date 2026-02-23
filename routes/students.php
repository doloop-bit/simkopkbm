<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->group(function () {
    Volt::route('/students', 'admin.data-master.students.index')->name('students.index');
    Volt::route('/students/class-placements', 'admin.academic.class-placement')->name('students.class-placement');
});
