<?php

declare(strict_types=1);

namespace App\Actions\OrganizationMembers;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final class RevokeOrganizationInvitationAction
{
    /**
     * @throws \Throwable
     */
    public function handle(User $member): void
    {
        DB::transaction(function () use ($member): void {
            // Hard-deletes the pending user; the organization_user pivot cascades on delete.
            $member->delete();
        });
    }
}
