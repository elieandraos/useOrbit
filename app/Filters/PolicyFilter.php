<?php

declare(strict_types=1);

namespace App\Filters;

use App\Enums\PolicyDisplayStatus;
use App\Enums\PolicyStatus;
use App\Support\Policies\PolicyDisplayStatusResolver;
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

    /**
     * Filter by display status, the SQL counterpart of PolicyDisplayStatus::resolve() with identical
     * boundaries, against the same organization-local date the resource uses.
     *
     * @noinspection PhpUnused
     */
    public function status(string $value): Builder
    {
        $today = app(PolicyDisplayStatusResolver::class)->today()->toDateString();

        return match (PolicyDisplayStatus::from($value)) {
            PolicyDisplayStatus::Cancelled => $this->builder->where('status', PolicyStatus::Cancelled->value),
            PolicyDisplayStatus::Frozen => $this->builder->where('status', PolicyStatus::Frozen->value),
            PolicyDisplayStatus::Upcoming => $this->builder
                ->where('status', PolicyStatus::Active->value)
                ->whereDate('effective_date', '>', $today),
            PolicyDisplayStatus::InForce => $this->builder
                ->where('status', PolicyStatus::Active->value)
                ->whereDate('effective_date', '<=', $today)
                ->whereDate('expiry_date', '>=', $today),
            PolicyDisplayStatus::Expired => $this->builder
                ->where('status', PolicyStatus::Active->value)
                ->whereDate('expiry_date', '<', $today),
        };
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
    public function currencyId(int|string $value): Builder
    {
        return $this->builder->where('currency_id', $value);
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
        return $this->builder->whereRaw(self::NET_PREMIUM.' >= CAST(? AS DECIMAL(15, 2))', [$value]);
    }

    /** @noinspection PhpUnused */
    public function amountMax(int|string $value): Builder
    {
        return $this->builder->whereRaw(self::NET_PREMIUM.' <= CAST(? AS DECIMAL(15, 2))', [$value]);
    }
}
