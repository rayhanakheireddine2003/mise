<?php

use App\Http\Controllers\IngredientController;
use App\Http\Controllers\IngredientUnitController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StorageAreaController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // Breeze: profile pages
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | Mise routes: one block per team member.
    | Only add routes inside YOUR block, so our pull requests don't conflict.
    | Each block is locked with the "role" middleware (MISE-2): anyone whose
    | role is not listed gets a 403 page.
    |--------------------------------------------------------------------------
    */

    // ===== Member 1: roles and menu (MISE-2, MISE-3) =====
    // (no pages: the menu list is in app/View/MenuLinks.php)

    // ===== Member 2: storage areas and ingredients (MISE-4..6) =====
    Route::middleware('role:owner,chef')->group(function () {
        Route::resource('storage-areas', StorageAreaController::class)
            ->only(['index', 'store', 'destroy']);
        Route::resource('ingredients', IngredientController::class)->except(['show']);
        Route::post('ingredients/{ingredient}/units', [IngredientUnitController::class, 'store'])
            ->name('ingredients.units.store');
        Route::delete('ingredient-units/{unit}', [IngredientUnitController::class, 'destroy'])
            ->name('ingredient-units.destroy');
    });

    // ===== Member 3: suppliers and prices (MISE-7..9) =====
    Route::middleware('role:owner,storekeeper')->group(function () {
        Route::resource('suppliers', SupplierController::class)->except(['show']);
    });

    // ===== Member 4: recipes (MISE-10..12) =====
    // Wrap in: Route::middleware('role:owner,chef')->group(function () { ... });
});

require __DIR__.'/auth.php';
