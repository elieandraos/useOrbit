<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\OrganizationRole;
use App\Models\User;

final class OrganizationPolicy
{
    public function update(User $user): bool
    {
        return $user->organization_id !== null
            && $user->role === OrganizationRole::Owner;
    }
}
