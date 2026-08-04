<?php

declare(strict_types=1);

use App\Http\Controllers\Agents\AgentsArchiveController;
use App\Http\Controllers\Agents\AgentsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'organization'])->group(function () {
    Route::get('agents', [AgentsController::class, 'index'])->name('agents.index');
    Route::get('agents/create', [AgentsController::class, 'create'])->name('agents.create');
    Route::post('agents', [AgentsController::class, 'store'])->name('agents.store');
    Route::get('agents/{agent:slug}', [AgentsController::class, 'show'])->name('agents.show');
    Route::get('agents/{agent:slug}/edit', [AgentsController::class, 'edit'])->name('agents.edit');
    Route::patch('agents/{agent:slug}', [AgentsController::class, 'update'])->name('agents.update');
    Route::patch('agents/{agent:slug}/archive', AgentsArchiveController::class)->name('agents.archive');
});
