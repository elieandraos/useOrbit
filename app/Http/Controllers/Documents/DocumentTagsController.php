<?php

declare(strict_types=1);

namespace App\Http\Controllers\Documents;

use App\Actions\Tags\AttachTagAction;
use App\Actions\Tags\DetachTagAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\TagResource;
use App\Models\Document;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Attributes\Controllers\Authorize;

final class DocumentTagsController extends Controller
{
    #[Authorize('view', 'document')]
    public function store(Document $document, Tag $tag, AttachTagAction $action): AnonymousResourceCollection
    {
        $action->handle($document, $tag);

        return TagResource::collection($this->tagsForDocument($document));
    }

    #[Authorize('view', 'document')]
    public function destroy(Document $document, Tag $tag, DetachTagAction $action): AnonymousResourceCollection
    {
        $action->handle($document, $tag);

        return TagResource::collection($this->tagsForDocument($document));
    }

    /** @return Collection<int, Tag> */
    private function tagsForDocument(Document $document): Collection
    {
        return $document->tags()
            ->withCount(['taggables' => function (Builder $query) use ($document): void {
                /** @noinspection PhpUndefinedMethodInspection */
                $query->forDocumentableType($document->documentable_type);
            }])
            ->get();
    }
}
