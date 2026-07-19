<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\DocumentStatus;
use App\Enums\OrganizationRole;
use App\Models\Contracts\Documentable;
use App\Models\Document;
use App\Models\User;

final class DocumentPolicy
{
    public function viewAny(User $user, Documentable $documentable): bool
    {
        return $user->current_organization_id !== null;
    }

    public function create(User $user, Documentable $documentable): bool
    {
        return $user->current_organization_id !== null;
    }

    public function view(User $user, Document $document): bool
    {
        return true;
    }

    public function delete(User $user, Document $document): bool
    {
        return $document->organization_id === $user->current_organization_id
            && $document->status !== DocumentStatus::Pending
            && ($user->organizationRole() === OrganizationRole::Owner || $document->uploaded_by === $user->id);
    }
}
