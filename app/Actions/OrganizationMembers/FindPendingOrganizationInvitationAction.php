<?php

declare(strict_types=1);

namespace App\Actions\OrganizationMembers;

use App\Models\OrganizationMember;

final class FindPendingOrganizationInvitationAction
{
    public function handle(string $token): ?OrganizationMember
    {
        /** @noinspection PhpUndefinedMethodInspection */
        /** @var OrganizationMember|null $invitation */
        $invitation = OrganizationMember::query()
            ->pendingInvitation()
            ->where('token', hash('sha256', $token))
            ->with(['user', 'organization', 'inviter'])
            ->first();

        return $invitation;
    }
}
