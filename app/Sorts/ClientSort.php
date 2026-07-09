<?php

declare(strict_types=1);

namespace App\Sorts;

use Illuminate\Database\Eloquent\Builder;

final class ClientSort extends Sort
{
    /** @noinspection PhpUnused */
    public function name(string $direction): Builder
    {
        return $this->builder
            ->orderBy('first_name', $direction)
            ->orderBy('last_name', $direction);
    }

    /** @noinspection PhpUnused */
    public function email(string $direction): Builder
    {
        return $this->builder->orderBy('email', $direction);
    }

    /** @noinspection PhpUnused */
    public function enrollmentDate(string $direction): Builder
    {
        return $this->builder->orderBy('enrollment_date', $direction);
    }

    protected function default(Builder $builder): Builder
    {
        return $builder->orderBy('enrollment_date', 'desc');
    }
}
