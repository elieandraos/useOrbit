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
        return $user->organization_id !== null;
    }

    public function manage(User $user): bool
    {
        return $user->organization_id !== null
            && $user->role->isPrivileged();
    }

    public function invite(User $user): bool
    {
        return $user->organization_id !== null
            && $user->role->isPrivileged();
    }

    public function changeRole(User $user, User $member): bool
    {
        if ($member->is($user)) {
            return false;
        }

        if (! $user->role->isPrivileged()) {
            return false;
        }

        return $member->organization_id === $user->organization_id
            && $member->role !== OrganizationRole::Owner
            && $member->status === OrganizationMemberStatus::Active;
    }

    public function remove(User $user, User $member): bool
    {
        if ($member->is($user)) {
            return false;
        }

        if (! $user->role->isPrivileged()) {
            return false;
        }

        return $member->organization_id === $user->organization_id
            && $member->role !== OrganizationRole::Owner
            && $member->status === OrganizationMemberStatus::Active;
    }

    public function revoke(User $user, User $member): bool
    {
        if (! $user->role->isPrivileged()) {
            return false;
        }

        return $member->organization_id === $user->organization_id
            && $member->status === OrganizationMemberStatus::Invited;
    }

    public function resetTwoFactor(User $user, User $member): bool
    {
        if ($member->is($user)) {
            return false;
        }

        if (! $user->role->isPrivileged()) {
            return false;
        }

        if ($member->organization_id !== $user->organization_id
            || $member->status !== OrganizationMemberStatus::Active) {
            return false;
        }

        if ($member->role === OrganizationRole::Owner) {
            return $user->role === OrganizationRole::Owner;
        }

        return true;
    }
}
