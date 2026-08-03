<?php

declare(strict_types=1);

namespace App\Filters;

use App\Enums\CarrierStatus;
use Illuminate\Database\Eloquent\Builder;

final class CarrierFilter extends QueryFilter
{
    /** @noinspection PhpUnused */
    public function search(string $value): Builder
    {
        return $this->builder->where(function (Builder $query) use ($value): void {
            $query->where('name', 'like', "%$value%")
                ->orWhere('phone', 'like', "%$value%")
                ->orWhere('website', 'like', "%$value%");
        });
    }

    /** @noinspection PhpUnused */
    public function archived(bool|string $value): Builder
    {
        $status = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? CarrierStatus::Archived : CarrierStatus::Active;

        return $this->builder->where('status', $status);
    }
}
