<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class OrganizationMemberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->current_organization_id !== null;
    }

    public function invite(User $user): bool
    {
        return $user->current_organization_id !== null
            && ($user->organizationRole()?->isPrivileged() ?? false);
    }
}
