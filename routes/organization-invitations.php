<?php

declare(strict_types=1);

use App\Http\Controllers\OrganizationInvitations\AcceptOrganizationInvitationController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('invitations/{token}', [AcceptOrganizationInvitationController::class, 'show'])->name('invitations.show');
    Route::post('invitations/{token}', [AcceptOrganizationInvitationController::class, 'store'])->name('invitations.store');
});
