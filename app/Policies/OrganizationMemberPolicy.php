<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
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

    public function changeRole(User $user, User $member): bool
    {
        if ($member->is($user)) {
            return false;
        }

        if (! ($user->organizationRole()?->isPrivileged() ?? false)) {
            return false;
        }

        $pivot = $member->organizations()
            ->wherePivot('organization_id', $user->current_organization_id)
            ->first()
            ?->pivot;

        return $pivot !== null
            && $pivot->role !== OrganizationRole::Owner
            && $pivot->status === OrganizationMemberStatus::Active;
    }
}
