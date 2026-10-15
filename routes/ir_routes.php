<?php

use App\Http\Controllers\IncidentReportController;
use App\Http\Controllers\IncidentReviewController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('ir')->name('ir.')->group(function () {
    Route::get('/', [IncidentReportController::class, 'index'])->name('index');
    Route::get('/create', [IncidentReportController::class, 'create'])->name('create');
    Route::post('/', [IncidentReportController::class, 'store'])->name('store');
    Route::get('/{incidentReport}', [IncidentReportController::class, 'show'])->name('show');
    Route::get('/{incidentReport}/edit', [IncidentReportController::class, 'edit'])->name('edit');
    Route::put('/{incidentReport}', [IncidentReportController::class, 'update'])->name('update');
    Route::post('/{incidentReport}/review', [IncidentReviewController::class, 'store'])->name('review');
    
    // New PDF Download Routes for QA
    Route::get('/{incidentReport}/download-feedback', [IncidentReportController::class, 'downloadFeedback'])->name('download.feedback');
    Route::get('/{incidentReport}/download-rca', [IncidentReportController::class, 'downloadRca'])->name('download.rca');
    Route::get('/{incidentReport}/download-completed', [IncidentReportController::class, 'downloadCompleted'])->name('download.completed');
});