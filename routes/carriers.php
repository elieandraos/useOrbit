<?php

declare(strict_types=1);

use App\Http\Controllers\Carriers\CarriersArchiveController;
use App\Http\Controllers\Carriers\CarriersBranchController;
use App\Http\Controllers\Carriers\CarriersController;
use App\Http\Controllers\Carriers\CarriersExcelExportController;
use App\Http\Controllers\Carriers\CarriersPdfExportController;
use App\Http\Controllers\Carriers\CarriersUnarchiveController;
use App\Http\Controllers\Notifications\NotifyCarrierController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'organization'])->group(function () {
    Route::get('carriers', [CarriersController::class, 'index'])->name('carriers.index');
    Route::get('carriers/create', [CarriersController::class, 'create'])->name('carriers.create');
    Route::post('carriers', [CarriersController::class, 'store'])->name('carriers.store');
    Route::get('carriers/export', CarriersExcelExportController::class)->name('carriers.export');
    Route::get('carriers/{carrier:slug}', [CarriersController::class, 'show'])->name('carriers.show');
    Route::get('carriers/{carrier:slug}/edit', [CarriersController::class, 'edit'])->name('carriers.edit');
    Route::patch('carriers/{carrier:slug}', [CarriersController::class, 'update'])->name('carriers.update');
    Route::patch('carriers/{carrier:slug}/archive', CarriersArchiveController::class)->name('carriers.archive');
    Route::patch('carriers/{carrier:slug}/unarchive', CarriersUnarchiveController::class)->name('carriers.unarchive');
    Route::post('carriers/{carrier:slug}/notify', NotifyCarrierController::class)->name('carriers.notify');
    Route::delete('carriers/{carrier:slug}', [CarriersController::class, 'destroy'])->name('carriers.destroy');
    Route::get('carriers/{carrier:slug}/export', CarriersPdfExportController::class)->name('carriers.export-pdf');
    Route::post('carriers/{carrier:slug}/branches', [CarriersBranchController::class, 'store'])->name('carriers.branches.store');
});

Route::middleware(['auth', 'organization'])->prefix('carriers/branches')->name('carriers.branches.')->group(function () {
    Route::patch('{branch}', [CarriersBranchController::class, 'update'])->name('update');
    Route::delete('{branch}', [CarriersBranchController::class, 'destroy'])->name('destroy');
});
