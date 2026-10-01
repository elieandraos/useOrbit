<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Client;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Query\Expression;

/**
 * A model's clients are the distinct, non-deleted clients holding at least one live policy with it,
 * whatever that policy's status. A client with several such policies counts once.
 */
trait HasPolicyClients
{
    abstract public function clients(): HasManyThrough;

    /**
     * Adds a `clients_count` attribute holding the number of distinct clients.
     */
    #[Scope]
    protected function withClientsCount(Builder $query): Builder
    {
        return $query->withAggregate('clients as clients_count', $this->distinctClientKey(), 'count');
    }

    /**
     * Counts this model's distinct clients without adding attributes to the instance.
     */
    public function countClients(): int
    {
        return (int) static::query()->whereKey($this->getKey())->withClientsCount()->value('clients_count');
    }

    private function distinctClientKey(): Expression
    {
        return new Expression('distinct '.(new Client)->getQualifiedKeyName());
    }
}
