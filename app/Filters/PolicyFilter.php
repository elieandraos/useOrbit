<?php

declare(strict_types=1);

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

final class PolicyFilter extends QueryFilter
{
    /**
     * Net premium as the index Amount column shows it: premium minus discount, with a
     * missing discount counted as zero.
     */
    private const string NET_PREMIUM = 'premium_amount - COALESCE(discount_amount, 0)';

    /** @noinspection PhpUnused */
    public function search(string $value): Builder
    {
        return $this->builder->where(function (Builder $query) use ($value): void {
            $query->where('policy_number', 'like', "%$value%")
                ->orWhere('subclass', 'like', "%$value%");
        });
    }

    /** @noinspection PhpUnused */
    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }

    /** @noinspection PhpUnused */
    public function type(string $value): Builder
    {
        return $this->builder->where('type', $value);
    }

    /**
     * @param  array<int, string>  $value
     *
     * @noinspection PhpUnused
     */
    public function class(array $value): Builder
    {
        if ($value === []) {
            return $this->builder;
        }

        return $this->builder->whereIn('class', $value);
    }

    /** @noinspection PhpUnused */
    public function carrierId(int|string $value): Builder
    {
        return $this->builder->where('carrier_id', $value);
    }

    /** @noinspection PhpUnused */
    public function source(string $value): Builder
    {
        return $this->builder->where('source', $value);
    }

    /** @noinspection PhpUnused */
    public function effectiveFrom(string $value): Builder
    {
        return $this->builder->whereDate('effective_date', '>=', $value);
    }

    /** @noinspection PhpUnused */
    public function effectiveTo(string $value): Builder
    {
        return $this->builder->whereDate('effective_date', '<=', $value);
    }

    /** @noinspection PhpUnused */
    public function amountMin(int|string $value): Builder
    {
        return $this->builder->whereRaw(self::NET_PREMIUM.' >= CAST(? AS DECIMAL(12, 2))', [$value]);
    }

    /** @noinspection PhpUnused */
    public function amountMax(int|string $value): Builder
    {
        return $this->builder->whereRaw(self::NET_PREMIUM.' <= CAST(? AS DECIMAL(12, 2))', [$value]);
    }
}
