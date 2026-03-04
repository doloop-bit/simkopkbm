<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->group(function () {
    Route::livewire('/financial/categories', 'admin.financial.categories')->name('financial.categories');
    Route::livewire('/financial/billings', 'admin.financial.billings')->name('financial.billings');
    Route::livewire('/financial/discounts', 'admin.financial.discounts')->name('financial.discounts');
    Route::livewire('/financial/transactions', 'admin.financial.transactions')->name('financial.transactions');

    // Master Budget Data
    Route::livewire('/financial/budget-categories', 'admin.financial.budget-categories')->name('financial.budget-categories');
    Route::livewire('/financial/standard-items', 'admin.financial.standard-budget-items')->name('financial.standard-items');

    // RAB / Budget Plans
    Route::livewire('/financial/budget-plans', 'admin.financial.budget-plans')->name('financial.budget-plans');

    Route::livewire('/reports', 'admin.reports')->name('reports');
});
