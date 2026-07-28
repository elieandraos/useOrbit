<?php

declare(strict_types=1);

namespace App\Actions\Tags;

use App\Models\Tag;

final class DeleteTagAction
{
    public function handle(Tag $tag): void
    {
        $tag->delete();
    }
}
