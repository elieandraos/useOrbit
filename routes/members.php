<?php

declare(strict_types=1);

use App\Http\Controllers\Policies\PolicyMembersController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'organization'])->group(function () {
    Route::get('policies/{policy:slug}/members', [PolicyMembersController::class, 'index'])->name('policies.members.index');
});
