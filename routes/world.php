<?php

declare(strict_types=1);

use App\Http\Controllers\World\StatesController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'organization'])->prefix('world')->name('world.')->group(function () {
    Route::get('states', StatesController::class)->name('states.index');
});
