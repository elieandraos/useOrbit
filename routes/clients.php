<?php

declare(strict_types=1);

use App\Http\Controllers\ClientsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'organization'])->group(function () {
    Route::get('clients', [ClientsController::class, 'index'])->name('clients.index');
    Route::get('clients/create', [ClientsController::class, 'create'])->name('clients.create');
    Route::post('clients', [ClientsController::class, 'store'])->name('clients.store');
    Route::get('clients/{client:slug}', [ClientsController::class, 'show'])->name('clients.show');
});
