<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->current_organization_id !== null;
    }

    public function view(User $user, Client $client): bool
    {
        return $client->organization_id === $user->current_organization_id;
    }

    public function create(User $user): bool
    {
        return $user->current_organization_id !== null;
    }

    public function update(User $user, Client $client): bool
    {
        return $client->organization_id === $user->current_organization_id;
    }

    public function delete(User $user, Client $client): bool
    {
        return $client->organization_id === $user->current_organization_id
            && $user->organizationRole() === 'owner';
    }
}
