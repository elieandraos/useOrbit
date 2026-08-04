<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class AgentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->current_organization_id !== null;
    }
}
