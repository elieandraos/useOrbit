<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Contracts\Taggable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

final class TagAttachment extends Model
{
    protected $table = 'taggables';

    #[Scope]
    protected function forTaggableType(Builder $query, string $taggableType, ?string $ownerType = null): Builder
    {
        $query->where('taggable_type', $taggableType);

        /** @var class-string<Taggable&Model> $modelClass */
        $modelClass = Relation::getMorphedModel($taggableType);
        $ownerColumn = $modelClass::ownerColumn();

        return $query->when(
            $ownerType !== null && $ownerColumn !== null,
            fn (Builder $query): Builder => $query->whereIn('taggable_id', $modelClass::query()->where($ownerColumn, $ownerType)->select('id')),
        );
    }
}
