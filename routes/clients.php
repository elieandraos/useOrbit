<?php

declare(strict_types=1);

use App\Http\Controllers\ClientsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'organization'])->group(function () {
    Route::get('clients', [ClientsController::class, 'index'])->name('clients.index');
    Route::get('clients/{client:slug}', [ClientsController::class, 'show'])->name('clients.show');
});
