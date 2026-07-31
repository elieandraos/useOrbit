<?php

declare(strict_types=1);

namespace App\Actions\Tags;

use App\Models\Document;
use App\Models\Tag;

final class AttachTagAction
{
    public function handle(Document $document, Tag $tag): void
    {
        abort_if($document->organization_id !== $tag->organization_id, 404);

        $document->tags()->syncWithoutDetaching($tag->id);
    }
}
