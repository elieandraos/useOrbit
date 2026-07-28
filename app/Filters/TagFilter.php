<?php

declare(strict_types=1);

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

final class TagFilter extends QueryFilter
{
    /** @noinspection PhpUnused */
    public function documentableType(string $value): Builder
    {
        return $this->builder->whereHas('taggables', function (Builder $query) use ($value): void {
            /** @noinspection PhpUndefinedMethodInspection */
            $query->forDocumentableType($value);
        });
    }
}
