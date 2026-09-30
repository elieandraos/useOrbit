<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CountryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $iso2
 * @property string $iso3
 * @property string|null $phone_code
 * @property string|null $region
 * @property string|null $subregion
 */
#[Fillable(['name', 'iso2', 'iso3', 'phone_code', 'region', 'subregion'])]
final class Country extends Model
{
    /** @use HasFactory<CountryFactory> */
    use HasFactory;

    public function states(): HasMany
    {
        return $this->hasMany(State::class);
    }

    /**
     * Limit the query to the configured operating markets; none are matched when no market is configured.
     */
    #[Scope]
    protected function inMarkets(Builder $query): Builder
    {
        return $query->whereIn('iso2', config('markets.countries'));
    }
}
