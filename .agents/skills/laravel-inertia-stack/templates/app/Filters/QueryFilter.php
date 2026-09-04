<?php

declare(strict_types=1);

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Reusable implementation template — laravel-inertia-stack.
 *
 * Target path in a consuming project: app/Filters/QueryFilter.php
 *
 * Before installing: check whether app/Filters/QueryFilter.php (or an equivalent)
 * already exists in the target project. If it does, reconcile the two instead of
 * overwriting the existing file.
 *
 * Requires: a project-defined "filter" scope wired through the Filterable trait
 * (see Filterable.php in this same template set) on every filterable model.
 */
abstract class QueryFilter
{
    protected Builder $builder;

    /** @param  array<string, mixed>  $filters */
    public function __construct(protected array $filters = []) {}

    public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;

        foreach ($this->filters as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $method = Str::camel((string) $key);

            if (method_exists($this, $method)) {
                $this->builder = $this->$method($value);
            }
        }

        return $this->builder;
    }
}
