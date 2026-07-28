<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasTags
{
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')->withTimestamps();
    }

    /** @return Collection<int, Tag> */
    public function tagsWithAttachmentCounts(): Collection
    {
        $ownerColumn = static::ownerColumn();

        return $this->tags()
            ->withCount(['taggables' => function (Builder $query) use ($ownerColumn): void {
                /** @noinspection PhpUndefinedMethodInspection */
                $query->forTaggableType($this->getMorphClass(), $ownerColumn !== null ? $this->{$ownerColumn} : null);
            }])
            ->get();
    }
}
