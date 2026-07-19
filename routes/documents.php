<?php

declare(strict_types=1);

use App\Http\Controllers\DocumentsDestroyController;
use App\Http\Controllers\DocumentsUploadBatchController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'organization'])->prefix('documents')->name('documents.')->group(function () {
    Route::post('batch', DocumentsUploadBatchController::class)->name('batch');
    Route::delete('{document}', DocumentsDestroyController::class)->name('destroy');
});
