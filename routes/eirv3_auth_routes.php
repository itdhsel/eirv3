<?php

use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

// Only include this file if no existing Laravel authentication kit is installed.
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');
