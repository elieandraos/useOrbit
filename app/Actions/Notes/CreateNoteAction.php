<?php

declare(strict_types=1);

namespace App\Actions\Notes;

use App\Models\Contracts\Notable;
use App\Models\Note;
use App\Models\User;
use App\Support\Tenancy\OrganizationContext;

final class CreateNoteAction
{
    public function __construct(private readonly OrganizationContext $organizationContext) {}

    /**
     * @param  array{body: string, pinned?: bool}  $attributes
     */
    public function handle(User $user, Notable $notable, array $attributes): Note
    {
        /** @var Note $note */
        $note = $notable->notes()->create([
            'organization_id' => $this->organizationContext->id(),
            'created_by' => $user->id,
            'body' => $attributes['body'],
            'pinned' => $attributes['pinned'] ?? false,
        ]);

        return $note;
    }
}
