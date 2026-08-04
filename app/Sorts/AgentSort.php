<?php

declare(strict_types=1);

namespace App\Sorts;

use Illuminate\Database\Eloquent\Builder;

final class AgentSort extends Sort
{
    /** @noinspection PhpUnused */
    public function name(string $direction): Builder
    {
        return $this->builder
            ->orderBy('last_name', $direction)
            ->orderBy('first_name', $direction);
    }

    protected function default(Builder $builder): Builder
    {
        return $builder->orderBy('last_name')->orderBy('first_name');
    }
}
