<?php

declare(strict_types=1);

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

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

            $method = Str::camel($key);

            if (method_exists($this, $method)) {
                $this->builder = $this->$method($value);
            }
        }

        return $this->builder;
    }
}
