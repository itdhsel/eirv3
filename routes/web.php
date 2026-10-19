
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Eirv3DashboardController;
use App\Http\Controllers\Auth\LoginController;

Route::post('/login', [LoginController::class, 'authenticate'])->name('login.post');
Route::get('/', Eirv3DashboardController::class)
    ->middleware('auth')
    ->name('dashboard');

require __DIR__.'/eirv3_auth_routes.php';
require __DIR__.'/ir_routes.php';
require __DIR__.'/eirv3_admin_routes.php';
