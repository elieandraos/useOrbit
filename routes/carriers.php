<?php

declare(strict_types=1);

use App\Http\Controllers\Carriers\CarriersController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'organization'])->group(function () {
    Route::get('carriers', [CarriersController::class, 'index'])->name('carriers.index');
    Route::get('carriers/create', [CarriersController::class, 'create'])->name('carriers.create');
    Route::post('carriers', [CarriersController::class, 'store'])->name('carriers.store');
    Route::get('carriers/{carrier:slug}', [CarriersController::class, 'show'])->name('carriers.show');
});