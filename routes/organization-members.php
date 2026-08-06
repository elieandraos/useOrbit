<?php

declare(strict_types=1);

use App\Http\Controllers\OrganizationMembers\OrganizationMembersChangeRoleController;
use App\Http\Controllers\OrganizationMembers\OrganizationMembersController;
use App\Http\Controllers\OrganizationMembers\OrganizationMembersDestroyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'organization'])->group(function () {
    Route::get('organization-members', [OrganizationMembersController::class, 'index'])->name('organization-members.index');
    Route::post('organization-members', [OrganizationMembersController::class, 'store'])->name('organization-members.store');
    Route::patch('organization-members/{member}/change-role', OrganizationMembersChangeRoleController::class)->name('organization-members.change-role');
    Route::delete('organization-members/{member}', OrganizationMembersDestroyController::class)->name('organization-members.destroy');
});
