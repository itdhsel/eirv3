<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\IncidentReportController;
use App\Http\Controllers\IncidentReviewController;
use App\Http\Controllers\IncidentRcaController;
use App\Http\Controllers\IncidentFeedbackController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Middleware\EnsureEirv3SuperAdmin;

// ==========================================
// 1. AUTHENTICATION & DASHBOARD
// ==========================================
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/', DashboardController::class)->name('dashboard');
});

// ==========================================
// 2. INCIDENT REPORTING (IR & RCA)
// ==========================================
Route::prefix('ir')->name('ir.')->group(function () {
    
    // Public Routes (Accessible without login)
    Route::get('/create', [IncidentReportController::class, 'create'])->name('create');
    Route::post('/', [IncidentReportController::class, 'store'])->name('store');
    Route::post('/{incidentReport}/feedback', [IncidentFeedbackController::class, 'store'])->name('feedback.store');
    
    // Protected Routes (Requires login)
    Route::middleware('auth')->group(function () {
        
        // Main IR Management
        Route::get('/', [IncidentReportController::class, 'index'])->name('index');
        Route::get('/{incidentReport}', [IncidentReportController::class, 'show'])->name('show');
        Route::get('/{incidentReport}/edit', [IncidentReportController::class, 'edit'])->name('edit');
        Route::put('/{incidentReport}', [IncidentReportController::class, 'update'])->name('update');
        Route::post('/{incidentReport}/review', [IncidentReviewController::class, 'store'])->name('review');
        
        // RCA Workflow (Now safely inside the 'ir.' namespace)
        Route::get('/{incidentReport}/rca/create', [IncidentRcaController::class, 'create'])->name('rca.create');
        Route::post('/{incidentReport}/rca', [IncidentRcaController::class, 'store'])->name('rca.store');
        Route::get('/{incidentReport}/rca/show', [IncidentRcaController::class, 'show'])->name('rca.show');
        Route::post('/{incidentReport}/rca/review', [IncidentRcaController::class, 'review'])->name('rca.review');
        
        // PDF Downloads
        Route::get('/{incidentReport}/download-feedback', [IncidentReportController::class, 'downloadFeedback'])->name('download.feedback');
        Route::get('/{incidentReport}/download-rca', [IncidentReportController::class, 'downloadRca'])->name('download.rca');
        Route::get('/{incidentReport}/download-completed', [IncidentReportController::class, 'downloadCompleted'])->name('download.completed');
    });
});

// ==========================================
// 3. ADMIN MANAGEMENT
// ==========================================
Route::middleware(['auth', EnsureEirv3SuperAdmin::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Replaces the 5 manual user routes with a clean resource definition
        Route::resource('users', UserManagementController::class)->except(['show', 'destroy']);
    });