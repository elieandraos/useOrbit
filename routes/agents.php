<?php

declare(strict_types=1);

use App\Http\Controllers\Agents\AgentsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'organization'])->group(function () {
    Route::get('agents', [AgentsController::class, 'index'])->name('agents.index');
});
