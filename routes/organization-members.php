<?php

declare(strict_types=1);

use App\Http\Controllers\OrganizationMembers\OrganizationMembersChangeRoleController;
use App\Http\Controllers\OrganizationMembers\OrganizationMembersController;
use App\Http\Controllers\OrganizationMembers\OrganizationMembersResetTwoFactorController;
use App\Http\Controllers\OrganizationMembers\OrganizationMembersRevokeInvitationController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'organization'])->group(function () {
    Route::get('organization-members', [OrganizationMembersController::class, 'index'])->name('organization-members.index');
    Route::post('organization-members', [OrganizationMembersController::class, 'store'])->name('organization-members.store');
    Route::patch('organization-members/{member}/change-role', OrganizationMembersChangeRoleController::class)->name('organization-members.change-role');
    Route::delete('organization-members/{member}/revoke-invitation', OrganizationMembersRevokeInvitationController::class)->name('organization-members.revoke-invitation');
    Route::delete('organization-members/{member}/two-factor', OrganizationMembersResetTwoFactorController::class)
        ->middleware(RequirePassword::class)
        ->name('organization-members.reset-two-factor');
    Route::delete('organization-members/{member}', [OrganizationMembersController::class, 'destroy'])->name('organization-members.destroy');
});
