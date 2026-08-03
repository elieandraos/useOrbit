<?php

declare(strict_types=1);

namespace App\Sorts;

use Illuminate\Database\Eloquent\Builder;

final class CarrierSort extends Sort
{
    /** @noinspection PhpUnused */
    public function name(string $direction): Builder
    {
        return $this->builder->orderBy('name', $direction);
    }

    protected function default(Builder $builder): Builder
    {
        return $builder->orderBy('name');
    }
}
