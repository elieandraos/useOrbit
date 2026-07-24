<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\OrganizationRole;
use App\Models\Contracts\Notable;
use App\Models\Note;
use App\Models\User;

final class NotePolicy
{
    public function viewAny(User $user, Notable $notable): bool
    {
        return $notable->organization_id === $user->current_organization_id;
    }

    public function create(User $user, Notable $notable): bool
    {
        return $notable->organization_id === $user->current_organization_id;
    }

    public function view(User $user, Note $note): bool
    {
        return $note->organization_id === $user->current_organization_id;
    }

    public function update(User $user, Note $note): bool
    {
        return $note->organization_id === $user->current_organization_id
            && ($user->organizationRole() === OrganizationRole::Owner || $note->created_by === $user->id);
    }

    public function delete(User $user, Note $note): bool
    {
        return $note->organization_id === $user->current_organization_id
            && ($user->organizationRole() === OrganizationRole::Owner || $note->created_by === $user->id);
    }
}
