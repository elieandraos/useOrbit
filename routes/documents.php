<?php

declare(strict_types=1);

use App\Http\Controllers\Clients\ClientDocumentsController;
use App\Http\Controllers\Documents\DocumentsDestroyController;
use App\Http\Controllers\Documents\DocumentsDownloadController;
use App\Http\Controllers\Documents\DocumentsUploadBatchController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'organization'])->group(function () {
    Route::get('clients/{client:slug}/documents', [ClientDocumentsController::class, 'index'])->name('clients.documents.index');
    Route::post('clients/{client:slug}/documents', [ClientDocumentsController::class, 'store'])->name('clients.documents.store');
});

Route::middleware(['auth', 'verified', 'organization'])->prefix('documents')->name('documents.')->group(function () {
    Route::post('batch', DocumentsUploadBatchController::class)->name('batch');
    Route::get('{document}/download', DocumentsDownloadController::class)->name('download');
    Route::delete('{document}', DocumentsDestroyController::class)->name('destroy');
});
