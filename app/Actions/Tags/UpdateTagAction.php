<?php

declare(strict_types=1);

namespace App\Actions\Tags;

use App\Models\Tag;
use App\Models\User;

final class UpdateTagAction
{
    public function handle(User $user, Tag $tag, string $name): Tag
    {
        $tag->update([
            'name' => trim($name),
            'updated_by' => $user->id,
        ]);

        return $tag;
    }
}
