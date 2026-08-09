<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;

final class TagPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->organization_id !== null;
    }

    public function create(User $user): bool
    {
        return $user->organization_id !== null;
    }

    public function update(User $user, Tag $tag): bool
    {
        return $tag->organization_id === $user->organization_id
            && ($user->role->isPrivileged() || $tag->created_by === $user->id);
    }

    public function delete(User $user, Tag $tag): bool
    {
        return $tag->organization_id === $user->organization_id
            && ($user->role->isPrivileged() || $tag->created_by === $user->id);
    }
}
