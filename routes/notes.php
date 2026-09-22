<?php

declare(strict_types=1);

use App\Http\Controllers\Clients\ClientNotesController;
use App\Http\Controllers\Notes\NotesDestroyController;
use App\Http\Controllers\Notes\NotesUpdateController;
use App\Http\Controllers\Policies\PolicyNotesController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'organization'])->group(function () {
    Route::get('clients/{client:slug}/notes', [ClientNotesController::class, 'index'])->name('clients.notes.index');
    Route::post('clients/{client:slug}/notes', [ClientNotesController::class, 'store'])->name('clients.notes.store');

    Route::get('policies/{policy:slug}/notes', [PolicyNotesController::class, 'index'])->name('policies.notes.index');
    Route::post('policies/{policy:slug}/notes', [PolicyNotesController::class, 'store'])->name('policies.notes.store');
});

Route::middleware(['auth', 'organization'])->prefix('notes')->name('notes.')->group(function () {
    Route::patch('{note}', NotesUpdateController::class)->name('update');
    Route::delete('{note}', NotesDestroyController::class)->name('destroy');
});
