<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->group(function () {
    Volt::route('/financial/categories', 'admin.financial.categories')->name('financial.categories');
    Volt::route('/financial/billings', 'admin.financial.billings')->name('financial.billings');
    Volt::route('/financial/discounts', 'admin.financial.discounts')->name('financial.discounts');
    Volt::route('/financial/transactions', 'admin.financial.transactions')->name('financial.transactions');
    
    // Master Budget Data
    Volt::route('/financial/budget-categories', 'admin.financial.budget-categories')->name('financial.budget-categories');
    Volt::route('/financial/standard-items', 'admin.financial.standard-budget-items')->name('financial.standard-items');
    
    // RAB / Budget Plans
    Volt::route('/financial/budget-plans', 'admin.financial.budget-plans')->name('financial.budget-plans');

    Volt::route('/reports', 'admin.reports')->name('reports');
});
