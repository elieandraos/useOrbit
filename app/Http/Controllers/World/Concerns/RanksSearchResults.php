<?php

declare(strict_types=1);

namespace App\Http\Controllers\World\Concerns;

use Illuminate\Support\Collection;

trait RanksSearchResults
{
    /**
     * Rank prefix matches before substring matches and cap the result set.
     *
     * @param  Collection<int, array{id: int, name: string}>  $results
     * @return Collection<int, array{id: int, name: string}>
     */
    private function rankAndCap(Collection $results, ?string $search): Collection
    {
        return $results
            ->sortBy(function (array $item) use ($search): string {
                $name = mb_strtolower($item['name']);
                $prefixRank = $search !== null && str_starts_with($name, mb_strtolower($search)) ? '0' : '1';

                return "{$prefixRank}_$name";
            })
            ->take(20)
            ->values();
    }
}
