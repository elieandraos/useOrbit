<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\OrganizationRole;
use App\Models\Tag;
use App\Models\User;

final class TagPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->current_organization_id !== null;
    }

    public function create(User $user): bool
    {
        return $user->current_organization_id !== null;
    }

    public function delete(User $user, Tag $tag): bool
    {
        return $tag->organization_id === $user->current_organization_id
            && ($user->organizationRole() === OrganizationRole::Owner || $tag->created_by === $user->id);
    }
}
