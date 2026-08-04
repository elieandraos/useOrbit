<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Agent;
use App\Models\User;

final class AgentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->current_organization_id !== null;
    }

    public function view(User $user, Agent $agent): bool
    {
        return $agent->organization_id === $user->current_organization_id;
    }

    public function create(User $user): bool
    {
        return $user->current_organization_id !== null;
    }
}
