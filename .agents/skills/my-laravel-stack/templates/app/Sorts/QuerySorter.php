<?php

declare(strict_types=1);

namespace App\Sorts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Reusable implementation template — my-laravel-stack.
 *
 * Target path in a consuming project: app/Sorts/QuerySorter.php
 *
 * Before installing: check whether app/Sorts/QuerySorter.php (or an equivalent)
 * already exists in the target project. Reconcile rather than overwrite it.
 *
 * Requires: a project-defined "sort" scope wired through the Sortable trait
 * (see Sortable.php in this same template set) on every sortable model.
 */
abstract class QuerySorter
{
    protected Builder $builder;

    public function __construct(protected ?string $column, protected string $direction) {}

    public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;

        $sorted = $this->resolve();

        // Break ties on the primary key so pagination and tests get a stable,
        // repeatable order regardless of the database's query plan for the
        // primary sort column.
        return $sorted->orderBy($sorted->getModel()->getKeyName());
    }

    private function resolve(): Builder
    {
        if ($this->column === null) {
            return $this->default($this->builder);
        }

        $method = Str::camel($this->column);

        if (method_exists($this, $method)) {
            return $this->$method($this->direction);
        }

        return $this->default($this->builder);
    }

    abstract protected function default(Builder $builder): Builder;
}
