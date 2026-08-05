<?php

declare(strict_types=1);

use App\Http\Controllers\OrganizationMembers\OrganizationMembersController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'organization'])->group(function () {
    Route::post('organization-members', [OrganizationMembersController::class, 'store'])->name('organization-members.store');
});
