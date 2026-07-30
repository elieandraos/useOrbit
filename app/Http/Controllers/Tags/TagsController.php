<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tags;

use App\Actions\Tags\CreateTagAction;
use App\Actions\Tags\DeleteTagAction;
use App\Actions\Tags\UpdateTagAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tags\IndexTagRequest;
use App\Http\Requests\Tags\StoreTagRequest;
use App\Http\Requests\Tags\UpdateTagRequest;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;

final class TagsController extends Controller
{
    #[Authorize('viewAny', Tag::class)]
    public function index(IndexTagRequest $request): AnonymousResourceCollection
    {
        $taggableType = $request->validated('taggable_type');
        $ownerType = $request->validated('owner_type');
        $ownerId = $request->validated('owner_id');

        /** @noinspection PhpUndefinedMethodInspection */
        $tags = Tag::query()
            ->whereHas('taggables', function (Builder $query) use ($taggableType, $ownerType): void {
                /** @noinspection PhpUndefinedMethodInspection */
                $query->forTaggableType($taggableType, $ownerType);
            })
            ->withTaggableCount($taggableType, $ownerType, $ownerId)
            ->orderBy('name')
            ->get();

        return TagResource::collection($tags);
    }

    #[Authorize('create', Tag::class)]
    public function store(StoreTagRequest $request, CreateTagAction $action): TagResource
    {
        /** @var User $user */
        $user = $request->user();

        $tag = $action->handle($user, $request->validated('name'));

        return TagResource::make($tag);
    }

    #[Authorize('update', 'tag')]
    public function update(UpdateTagRequest $request, Tag $tag, UpdateTagAction $action): TagResource
    {
        $tag = $action->handle($tag, $request->validated('name'));

        return TagResource::make($tag);
    }

    #[Authorize('delete', 'tag')]
    public function destroy(Tag $tag, DeleteTagAction $action): Response
    {
        $action->handle($tag);

        return response()->noContent();
    }
}
