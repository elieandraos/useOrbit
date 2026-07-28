<?php

declare(strict_types=1);

namespace App\Actions\Tags;

use App\Models\Contracts\Taggable;
use App\Models\Tag;

final class AttachTagAction
{
    public function handle(Taggable $taggable, Tag $tag): void
    {
        $taggable->tags()->syncWithoutDetaching([$tag->id => ['organization_id' => $tag->organization_id]]);
    }
}
