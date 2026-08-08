<?php

declare(strict_types=1);

namespace App\Actions\Tags;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Str;

final class CreateTagAction
{
    public function handle(User $user, string $name): Tag
    {
        $name = trim($name);

        $tag = Tag::query()
            ->where('organization_id', $user->organization_id)
            ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
            ->first();

        if ($tag !== null) {
            return $tag->loadCount('documents');
        }

        /** @var Tag $tag */
        $tag = Tag::query()->create([
            'organization_id' => $user->organization_id,
            'name' => $name,
            'created_by' => $user->id,
        ]);

        return $tag->loadCount('documents');
    }
}
