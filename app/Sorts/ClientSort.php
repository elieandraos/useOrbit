<?php

declare(strict_types=1);

namespace App\Sorts;

use Illuminate\Database\Eloquent\Builder;

final class ClientSort extends Sort
{
    /** @noinspection PhpUnused */
    public function name(string $direction): Builder
    {
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        return $this->builder
            ->orderByRaw("CASE WHEN client_type = 'company' THEN company_name ELSE first_name END $direction")
            ->orderByRaw("CASE WHEN client_type = 'company' THEN company_name ELSE last_name END $direction");
    }

    /** @noinspection PhpUnused */
    public function type(string $direction): Builder
    {
        return $this->builder->orderBy('client_type', $direction);
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
