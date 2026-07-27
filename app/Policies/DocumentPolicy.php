<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\OrganizationRole;
use App\Models\Contracts\Documentable;
use App\Models\Document;
use App\Models\User;

/**
 * Access model: any org member may view, create, and finalize documents for
 * clients in their organization. Delete is intentionally stricter (owner or
 * uploader only) to limit accidental/malicious data loss; view/download stay
 * org-wide by design for this small-agency use case.
 */
final class DocumentPolicy
{
    public function viewAny(User $user, Documentable $documentable): bool
    {
        return $documentable->organization_id === $user->current_organization_id;
    }

    public function create(User $user, Documentable $documentable): bool
    {
        return $documentable->organization_id === $user->current_organization_id;
    }

    public function finalize(User $user): bool
    {
        return $user->current_organization_id !== null;
    }

    public function view(User $user, Document $document): bool
    {
        return $document->organization_id === $user->current_organization_id;
    }

    public function delete(User $user, Document $document): bool
    {
        return $document->organization_id === $user->current_organization_id
            && $document->status->isSettled()
            && ($user->organizationRole() === OrganizationRole::Owner || $document->uploaded_by === $user->id);
    }
}
