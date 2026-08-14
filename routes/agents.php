<?php

declare(strict_types=1);

use App\Http\Controllers\Agents\AgentsArchiveController;
use App\Http\Controllers\Agents\AgentsController;
use App\Http\Controllers\Agents\AgentsExcelExportController;
use App\Http\Controllers\Agents\AgentsPdfExportController;
use App\Http\Controllers\Agents\AgentsUnarchiveController;
use App\Http\Controllers\Notifications\NotifyAgentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'organization'])->group(function () {
    Route::get('agents', [AgentsController::class, 'index'])->name('agents.index');
    Route::get('agents/create', [AgentsController::class, 'create'])->name('agents.create');
    Route::post('agents', [AgentsController::class, 'store'])->name('agents.store');
    Route::get('agents/export', AgentsExcelExportController::class)->name('agents.export');
    Route::get('agents/{agent:slug}', [AgentsController::class, 'show'])->name('agents.show');
    Route::get('agents/{agent:slug}/edit', [AgentsController::class, 'edit'])->name('agents.edit');
    Route::patch('agents/{agent:slug}', [AgentsController::class, 'update'])->name('agents.update');
    Route::patch('agents/{agent:slug}/archive', AgentsArchiveController::class)->name('agents.archive');
    Route::patch('agents/{agent:slug}/unarchive', AgentsUnarchiveController::class)->name('agents.unarchive');
    Route::post('agents/{agent:slug}/notify', NotifyAgentController::class)->name('agents.notify');
    Route::delete('agents/{agent:slug}', [AgentsController::class, 'destroy'])->name('agents.destroy');
    Route::get('agents/{agent:slug}/export', AgentsPdfExportController::class)->name('agents.export-pdf');
});
