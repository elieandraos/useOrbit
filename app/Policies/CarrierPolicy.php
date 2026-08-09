<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Carrier;
use App\Models\User;

final class CarrierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->organization_id !== null;
    }

    public function view(User $user, Carrier $carrier): bool
    {
        return $carrier->organization_id === $user->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->organization_id !== null;
    }

    public function update(User $user, Carrier $carrier): bool
    {
        return $carrier->organization_id === $user->organization_id;
    }

    public function delete(User $user, Carrier $carrier): bool
    {
        return $carrier->organization_id === $user->organization_id
            && $user->role->isPrivileged();
    }

    public function archive(User $user, Carrier $carrier): bool
    {
        return $carrier->organization_id === $user->organization_id
            && $user->role->isPrivileged();
    }

    public function unarchive(User $user, Carrier $carrier): bool
    {
        return $carrier->organization_id === $user->organization_id
            && $user->role->isPrivileged();
    }
}
