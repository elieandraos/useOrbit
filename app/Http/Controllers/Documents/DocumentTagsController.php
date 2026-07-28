<?php

declare(strict_types=1);

namespace App\Http\Controllers\Documents;

use App\Actions\Tags\AttachTagAction;
use App\Actions\Tags\DetachTagAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\TagResource;
use App\Models\Document;
use App\Models\Tag;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Attributes\Controllers\Authorize;

final class DocumentTagsController extends Controller
{
    #[Authorize('view', 'document')]
    public function store(Document $document, Tag $tag, AttachTagAction $action): AnonymousResourceCollection
    {
        $action->handle($document, $tag);

        return TagResource::collection($document->tags()->withCount('taggables')->get());
    }

    #[Authorize('view', 'document')]
    public function destroy(Document $document, Tag $tag, DetachTagAction $action): AnonymousResourceCollection
    {
        $action->handle($document, $tag);

        return TagResource::collection($document->tags()->withCount('taggables')->get());
    }
}
