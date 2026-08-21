<?php

declare(strict_types=1);

use App\Http\Controllers\Clients\ClientsArchiveController;
use App\Http\Controllers\Clients\ClientsController;
use App\Http\Controllers\Clients\ClientsExcelExportController;
use App\Http\Controllers\Clients\ClientsPdfExportController;
use App\Http\Controllers\Clients\ClientsUnarchiveController;
use App\Http\Controllers\Notifications\NotifyClientController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'organization'])->group(function () {
    Route::get('clients', [ClientsController::class, 'index'])->name('clients.index');
    Route::get('clients/create', [ClientsController::class, 'create'])->name('clients.create');
    Route::post('clients', [ClientsController::class, 'store'])->name('clients.store');
    Route::get('clients/export', ClientsExcelExportController::class)->name('clients.export');
    Route::get('clients/{client:slug}', [ClientsController::class, 'show'])->name('clients.show');
    Route::get('clients/{client:slug}/edit', [ClientsController::class, 'edit'])->name('clients.edit');
    Route::patch('clients/{client:slug}', [ClientsController::class, 'update'])->name('clients.update');
    Route::patch('clients/{client:slug}/archive', ClientsArchiveController::class)->name('clients.archive');
    Route::patch('clients/{client:slug}/unarchive', ClientsUnarchiveController::class)->name('clients.unarchive');
    Route::post('clients/{client:slug}/notify', NotifyClientController::class)->name('clients.notify');
    Route::delete('clients/{client:slug}', [ClientsController::class, 'destroy'])->name('clients.destroy');
    Route::get('clients/{client:slug}/export', ClientsPdfExportController::class)->name('clients.export-pdf');
});
