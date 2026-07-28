<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class TagAttachment extends Model
{
    protected $table = 'taggables';

    #[Scope]
    protected function forDocumentableType(Builder $query, string $type): Builder
    {
        return $query
            ->where('taggable_type', (new Document)->getMorphClass())
            ->whereIn('taggable_id', Document::query()->where('documentable_type', $type)->select('id'));
    }
}
