<?php

declare(strict_types=1);

namespace App\Filters;

use App\Enums\AgentStatus;
use Illuminate\Database\Eloquent\Builder;

final class AgentFilter extends QueryFilter
{
    /** @noinspection PhpUnused */
    public function search(string $value): Builder
    {
        return $this->builder->where(function (Builder $query) use ($value): void {
            $query->where('first_name', 'like', "%$value%")
                ->orWhere('last_name', 'like', "%$value%")
                ->orWhere('phone', 'like', "%$value%")
                ->orWhere('email', 'like', "%$value%");
        });
    }

    /** @noinspection PhpUnused */
    public function archived(bool|string $value): Builder
    {
        $status = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? AgentStatus::Archived : AgentStatus::Active;

        return $this->builder->where('status', $status);
    }
}
