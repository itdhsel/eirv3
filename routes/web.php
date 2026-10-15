<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'dashboard')
    ->middleware('auth')
    ->name('dashboard');

require __DIR__.'/eirv3_auth_routes.php';
require __DIR__.'/ir_routes.php';
require __DIR__.'/eirv3_admin_routes.php';