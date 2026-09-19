<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::controller(\App\Http\Controllers\IngredientPurchaseController::class)->group(function (): void {
    Route::get('/ingredientes', 'index')->name('ingredients.index');
    Route::get('/ingredientes/{ingredient}', 'show')->name('ingredients.show');
    Route::get('/compras/nueva', 'create')->name('purchases.create');
    Route::post('/compras', 'store')->name('purchases.store');
});
