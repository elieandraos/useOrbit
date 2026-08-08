<?php

declare(strict_types=1);

namespace App\Actions\OrganizationMembers;

use App\Models\User;

final class FindPendingOrganizationInvitationAction
{
    public function handle(string $token): ?User
    {
        /** @noinspection PhpUndefinedMethodInspection */
        /** @var User|null $invitation */
        $invitation = User::query()
            ->pendingInvitation()
            ->where('invitation_token', hash('sha256', $token))
            ->with(['organization', 'inviter'])
            ->first();

        return $invitation;
    }
}
