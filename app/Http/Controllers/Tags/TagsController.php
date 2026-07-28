<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tags;

use App\Actions\Tags\CreateTagAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tags\StoreTagRequest;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Attributes\Controllers\Authorize;

final class TagsController extends Controller
{
    #[Authorize('viewAny', Tag::class)]
    public function index(): AnonymousResourceCollection
    {
        $tags = Tag::query()
            ->withCount('taggables')
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
}
