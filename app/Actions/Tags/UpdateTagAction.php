<?php

declare(strict_types=1);

namespace App\Actions\Tags;

use App\Models\Tag;

final class UpdateTagAction
{
    public function handle(Tag $tag, string $name): Tag
    {
        $tag->update(['name' => trim($name)]);

        return $tag;
    }
}
