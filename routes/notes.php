<?php

declare(strict_types=1);

use App\Http\Controllers\Clients\ClientNotesController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'organization'])->group(function () {
    Route::get('clients/{client:slug}/notes', [ClientNotesController::class, 'index'])->name('clients.notes.index');
    Route::post('clients/{client:slug}/notes', [ClientNotesController::class, 'store'])->name('clients.notes.store');
});
