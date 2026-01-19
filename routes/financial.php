<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Volt::route('/financial/categories', 'admin.financial.categories')->name('financial.categories');
    Volt::route('/financial/billings', 'admin.financial.billings')->name('financial.billings');
    Volt::route('/financial/payments', 'admin.financial.payments')->name('financial.payments');
    Volt::route('/reports', 'admin.reports')->name('reports');
});
