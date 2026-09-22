<?php

declare(strict_types=1);

namespace App\Sorts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class PolicySort extends Sort
{
    /** @noinspection PhpUnused */
    public function policyNumber(string $direction): Builder
    {
        return $this->builder->orderBy('policy_number', $direction);
    }

    /** @noinspection PhpUnused */
    public function client(string $direction): Builder
    {
        return $this->builder
            ->orderBy(
                DB::table('clients')
                    ->selectRaw("CASE WHEN client_type = 'company' THEN company_name ELSE first_name END")
                    ->whereColumn('clients.id', 'policies.client_id'),
                $direction,
            )
            ->orderBy(
                DB::table('clients')
                    ->selectRaw("CASE WHEN client_type = 'company' THEN company_name ELSE last_name END")
                    ->whereColumn('clients.id', 'policies.client_id'),
                $direction,
            );
    }

    /** @noinspection PhpUnused */
    public function effectiveDate(string $direction): Builder
    {
        return $this->builder->orderBy('effective_date', $direction);
    }

    /** @noinspection PhpUnused */
    public function amount(string $direction): Builder
    {
        return $this->builder->orderBy('premium_amount', $direction);
    }

    /** @noinspection PhpUnused */
    public function status(string $direction): Builder
    {
        return $this->builder->orderBy('status', $direction);
    }

    protected function default(Builder $builder): Builder
    {
        return $builder->orderBy('effective_date', 'desc');
    }
}
