<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\PolicyFireDetailsFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $policy_id
 * @property string $property_type
 * @property int $floor_area
 * @property int|null $year_built
 * @property string $street
 * @property string|null $building_floor
 * @property string $city
 * @property int|null $state_id
 * @property int|null $country_id
 * @property string $sum_insured
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Policy $policy
 * @property-read State|null $state
 * @property-read Country|null $country
 */
#[Fillable([
    'policy_id', 'property_type', 'floor_area', 'year_built', 'street', 'building_floor',
    'city', 'state_id', 'country_id', 'sum_insured',
])]
final class PolicyFireDetails extends Model
{
    /** @use HasFactory<PolicyFireDetailsFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'floor_area' => 'integer',
            'year_built' => 'integer',
            'sum_insured' => 'decimal:2',
        ];
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
