<?php

declare(strict_types=1);

namespace App\Actions\Tags;

use App\Models\Tag;
use App\Models\User;
use App\Support\Tenancy\OrganizationContext;
use Illuminate\Support\Str;

final class CreateTagAction
{
    public function __construct(private readonly OrganizationContext $organizationContext) {}

    public function handle(User $user, string $name): Tag
    {
        $name = trim($name);
        $organizationId = $this->organizationContext->id();

        $tag = Tag::query()
            ->where('organization_id', $organizationId)
            ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
            ->first();

        if ($tag !== null) {
            return $tag->loadCount('documents');
        }

        /** @var Tag $tag */
        $tag = Tag::query()->create([
            'organization_id' => $organizationId,
            'name' => $name,
            'created_by' => $user->id,
        ]);

        return $tag->loadCount('documents');
    }
}
