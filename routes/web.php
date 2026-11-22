<?php

use App\Http\Controllers\ProfileController;
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
    | The "role" middleware is added by Member 1 in MISE-2.
    |--------------------------------------------------------------------------
    */

    // ===== Member 1: roles and menu (MISE-2, MISE-3) =====

    // ===== Member 2: storage areas and ingredients (MISE-4..6) =====
    // Wrap in: Route::middleware('role:owner,chef')->group(function () { ... });

    // ===== Member 3: suppliers and prices (MISE-7..9) =====
    // Wrap in: Route::middleware('role:owner,storekeeper')->group(function () { ... });

    // ===== Member 4: recipes (MISE-10..12) =====
    // Wrap in: Route::middleware('role:owner,chef')->group(function () { ... });
});

require __DIR__.'/auth.php';
