<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Sorts\Sort;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

trait Sortable
{
    #[Scope]
    protected function sort(Builder $query, Sort $sort): Builder
    {
        return $sort->apply($query);
    }
}
