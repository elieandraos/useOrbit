<?php

declare(strict_types=1);

namespace App\Actions\Tags;

use App\Models\Document;
use App\Models\Tag;

final class DetachTagAction
{
    public function handle(Document $document, Tag $tag): void
    {
        $document->tags()->detach($tag->id);
    }
}
