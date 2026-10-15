<?php

use App\Http\Controllers\Eirv3LoginController;
use Illuminate\Support\Facades\Route;

// Only include this file if no existing Laravel authentication kit is installed.
Route::middleware('guest')->group(function () {
    Route::get('/login', [Eirv3LoginController::class, 'create'])->name('login');
    Route::post('/login', [Eirv3LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});
Route::post('/logout', [Eirv3LoginController::class, 'destroy'])->middleware('auth')->name('logout');
