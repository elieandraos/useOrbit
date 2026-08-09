<?php

declare(strict_types=1);

namespace App\Sorts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

abstract class Sort
{
    protected Builder $builder;

    public function __construct(protected ?string $column, protected string $direction) {}

    public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;

        $sorted = $this->resolve();

        // Ties on the primary sort column(s) are otherwise ordered however the
        // database engine feels like on a given query plan, which is not
        // guaranteed to stay consistent across executions. Break ties on the
        // primary key so pagination and tests get a stable, repeatable order.
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
