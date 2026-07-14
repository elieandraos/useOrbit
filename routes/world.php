<?php

declare(strict_types=1);

use App\Http\Controllers\World\CitiesController;
use App\Http\Controllers\World\StatesController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'organization'])->prefix('world')->name('world.')->group(function () {
    Route::get('states', StatesController::class)->name('states.index');
    Route::get('cities', CitiesController::class)->name('cities.index');
});
