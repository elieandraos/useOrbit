<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\OrganizationRole;
use App\Models\Carrier;
use App\Models\User;

final class CarrierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->current_organization_id !== null;
    }

    public function view(User $user, Carrier $carrier): bool
    {
        return $carrier->organization_id === $user->current_organization_id;
    }

    public function create(User $user): bool
    {
        return $user->current_organization_id !== null;
    }

    public function update(User $user, Carrier $carrier): bool
    {
        return $carrier->organization_id === $user->current_organization_id;
    }

    public function delete(User $user, Carrier $carrier): bool
    {
        return $carrier->organization_id === $user->current_organization_id
            && $user->organizationRole() === OrganizationRole::Owner;
    }

    public function archive(User $user, Carrier $carrier): bool
    {
        return $carrier->organization_id === $user->current_organization_id
            && $user->organizationRole() === OrganizationRole::Owner;
    }

    public function unarchive(User $user, Carrier $carrier): bool
    {
        return $carrier->organization_id === $user->current_organization_id
            && $user->organizationRole() === OrganizationRole::Owner;
    }
}
