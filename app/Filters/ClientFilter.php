<?php

declare(strict_types=1);

namespace App\Filters;

use App\Enums\ClientStatus;
use Illuminate\Database\Eloquent\Builder;

final class ClientFilter extends QueryFilter
{
    /** @noinspection PhpUnused */
    public function search(string $value): Builder
    {
        return $this->builder->where(function (Builder $query) use ($value): void {
            $query->where('first_name', 'like', "%$value%")
                ->orWhere('middle_name', 'like', "%$value%")
                ->orWhere('last_name', 'like', "%$value%")
                ->orWhere('phone', 'like', "%$value%")
                ->orWhere('email', 'like', "%$value%");
        });
    }

    /** @noinspection PhpUnused */
    public function gender(string $value): Builder
    {
        return $this->builder->where('gender', $value);
    }

    /** @noinspection PhpUnused */
    public function enrolledFrom(string $value): Builder
    {
        return $this->builder->whereDate('enrollment_date', '>=', $value);
    }

    /** @noinspection PhpUnused */
    public function enrolledTo(string $value): Builder
    {
        return $this->builder->whereDate('enrollment_date', '<=', $value);
    }

    /** @noinspection PhpUnused */
    public function ageMin(int|string $value): Builder
    {
        return $this->builder->whereDate('date_of_birth', '<=', now()->subYears((int) $value)->toDateString());
    }

    /** @noinspection PhpUnused */
    public function ageMax(int|string $value): Builder
    {
        return $this->builder->whereDate('date_of_birth', '>', now()->subYears((int) $value + 1)->toDateString());
    }

    /** @noinspection PhpUnused */
    public function archived(bool|string $value): Builder
    {
        $status = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? ClientStatus::Archived : ClientStatus::Active;

        return $this->builder->where('status', $status);
    }
}
