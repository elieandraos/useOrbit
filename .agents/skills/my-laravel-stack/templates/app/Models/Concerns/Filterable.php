<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Filters\QueryFilter;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Reusable implementation template — my-laravel-stack.
 *
 * Target path in a consuming project: app/Models/Concerns/Filterable.php
 *
 * Requires a Laravel version that provides the
 * Illuminate\Database\Eloquent\Attributes\Scope attribute — confirm this class
 * exists in the target project's installed laravel/framework version before
 * relying on it. Add `use Filterable;` to any model that should accept a
 * QueryFilter subclass via the `filter` scope.
 *
 * Before installing: check whether an equivalent trait already exists in the
 * target project's app/Models/Concerns/ directory. Reconcile rather than
 * overwrite it.
 */
trait Filterable
{
    #[Scope]
    protected function filter(Builder $query, QueryFilter $filters): Builder
    {
        return $filters->apply($query);
    }
}
