<?php

declare(strict_types=1);

use App\Http\Controllers\ClientsArchiveController;
use App\Http\Controllers\ClientsController;
use App\Http\Controllers\ClientsExcelExportController;
use App\Http\Controllers\ClientsPdfExportController;
use App\Http\Controllers\ClientsUnarchiveController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'organization'])->group(function () {
    Route::get('clients', [ClientsController::class, 'index'])->name('clients.index');
    Route::get('clients/create', [ClientsController::class, 'create'])->name('clients.create');
    Route::post('clients', [ClientsController::class, 'store'])->name('clients.store');
    Route::get('clients/export', ClientsExcelExportController::class)->name('clients.export');
    Route::get('clients/{client:slug}', [ClientsController::class, 'show'])->name('clients.show');
    Route::get('clients/{client:slug}/edit', [ClientsController::class, 'edit'])->name('clients.edit');
    Route::patch('clients/{client:slug}', [ClientsController::class, 'update'])->name('clients.update');
    Route::patch('clients/{client:slug}/archive', ClientsArchiveController::class)->name('clients.archive');
    Route::patch('clients/{client:slug}/unarchive', ClientsUnarchiveController::class)->name('clients.unarchive');
    Route::delete('clients/{client:slug}', [ClientsController::class, 'destroy'])->name('clients.destroy');
    Route::get('clients/{client:slug}/export', ClientsPdfExportController::class)->name('clients.export-pdf');
});
