<?php

declare(strict_types=1);

use App\Http\Controllers\Carriers\CarriersController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'organization'])->group(function () {
    Route::get('carriers', [CarriersController::class, 'index'])->name('carriers.index');
});