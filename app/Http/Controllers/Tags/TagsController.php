<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tags;

use App\Http\Controllers\Controller;
use App\Http\Resources\TagResource;
use App\Models\Tag;
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
}
