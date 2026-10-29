<?php

use App\Http\Controllers\IncidentReportController;
use App\Http\Controllers\IncidentReviewController;
use App\Http\Controllers\IncidentRcaController;
use Illuminate\Support\Facades\Route;

// PUBLIC ROUTES: Accessible to everyone (guests and logged-in users)
Route::prefix('ir')->name('ir.')->group(function () {
    Route::get('/create', [IncidentReportController::class, 'create'])->name('create');
    Route::post('/', [IncidentReportController::class, 'store'])->name('store');
    Route::post('/{incidentReport}/feedback', [\App\Http\Controllers\IncidentFeedbackController::class, 'store'])->name('feedback.store');
});

// PROTECTED ROUTES: Only accessible to logged-in users
Route::middleware('auth')->prefix('ir')->name('ir.')->group(function () {
    Route::get('/', [IncidentReportController::class, 'index'])->name('index');
    Route::get('/{incidentReport}', [IncidentReportController::class, 'show'])->name('show');
    Route::get('/{incidentReport}/edit', [IncidentReportController::class, 'edit'])->name('edit');
    Route::put('/{incidentReport}', [IncidentReportController::class, 'update'])->name('update');
    Route::post('/{incidentReport}/review', [IncidentReviewController::class, 'store'])->name('review');
    
    // PDF Download Routes for QA
    Route::get('/{incidentReport}/download-feedback', [IncidentReportController::class, 'downloadFeedback'])->name('download.feedback');
    Route::get('/{incidentReport}/download-rca', [IncidentReportController::class, 'downloadRca'])->name('download.rca');
    Route::get('/{incidentReport}/download-completed', [IncidentReportController::class, 'downloadCompleted'])->name('download.completed');
});

// Add this inside your protected Route::middleware('auth')->prefix('ir')->name('ir.')->group(...)
Route::get('/{incidentReport}/rca/create', [IncidentRcaController::class, 'create'])->name('rca.create');
Route::post('/{incidentReport}/rca', [IncidentRcaController::class, 'store'])->name('rca.store');
// View the submitted RCA (Read-only with Approval buttons for HOD/QA)
Route::get('/{incidentReport}/rca/show', [IncidentRcaController::class, 'show'])->name('rca.show');

// Process the HOD or QA approval/rejection
Route::post('/{incidentReport}/rca/review', [IncidentRcaController::class, 'review'])->name('rca.review');