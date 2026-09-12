<?php

declare(strict_types=1);

use App\Http\Controllers\Policies\PoliciesController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'organization'])->group(function () {
    Route::get('policies', [PoliciesController::class, 'index'])->name('policies.index');
});
