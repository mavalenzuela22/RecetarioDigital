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

Route::controller(\App\Http\Controllers\RecipeController::class)->group(function (): void {
    Route::get('/recetas', 'index')->name('recipes.index');
    Route::get('/recetas/nueva', 'create')->name('recipes.create');
    Route::get('/recetas/{recipe}/editar', 'edit')->name('recipes.edit');
    Route::get('/recetas/{recipe}', 'show')->name('recipes.show');
    Route::post('/recetas', 'store')->name('recipes.store');
    Route::post('/recetas/{recipe}/versiones', 'update')->name('recipes.update');
});
