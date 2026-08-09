<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

final class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->organization_id !== null;
    }

    public function view(User $user, Client $client): bool
    {
        return $client->organization_id === $user->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->organization_id !== null;
    }

    public function update(User $user, Client $client): bool
    {
        return $client->organization_id === $user->organization_id;
    }

    public function delete(User $user, Client $client): bool
    {
        return $client->organization_id === $user->organization_id
            && $user->role->isPrivileged();
    }

    public function archive(User $user, Client $client): bool
    {
        return $client->organization_id === $user->organization_id
            && $user->role->isPrivileged();
    }

    public function unarchive(User $user, Client $client): bool
    {
        return $client->organization_id === $user->organization_id
            && $user->role->isPrivileged();
    }
}
