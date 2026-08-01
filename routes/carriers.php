<?php

declare(strict_types=1);

use App\Http\Controllers\Carriers\CarriersArchiveController;
use App\Http\Controllers\Carriers\CarriersController;
use App\Http\Controllers\Carriers\CarriersUnarchiveController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'organization'])->group(function () {
    Route::get('carriers', [CarriersController::class, 'index'])->name('carriers.index');
    Route::get('carriers/create', [CarriersController::class, 'create'])->name('carriers.create');
    Route::post('carriers', [CarriersController::class, 'store'])->name('carriers.store');
    Route::get('carriers/{carrier:slug}', [CarriersController::class, 'show'])->name('carriers.show');
    Route::get('carriers/{carrier:slug}/edit', [CarriersController::class, 'edit'])->name('carriers.edit');
    Route::patch('carriers/{carrier:slug}', [CarriersController::class, 'update'])->name('carriers.update');
    Route::patch('carriers/{carrier:slug}/archive', CarriersArchiveController::class)->name('carriers.archive');
    Route::patch('carriers/{carrier:slug}/unarchive', CarriersUnarchiveController::class)->name('carriers.unarchive');
});