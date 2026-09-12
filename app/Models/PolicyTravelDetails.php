<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\PolicyTravelDetailsFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $policy_id
 * @property string $destination
 * @property CarbonImmutable $trip_start_date
 * @property CarbonImmutable $trip_end_date
 * @property string $travelers
 * @property string $coverage_tier
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Policy $policy
 */
#[Fillable([
    'policy_id', 'destination', 'trip_start_date', 'trip_end_date', 'travelers', 'coverage_tier',
])]
final class PolicyTravelDetails extends Model
{
    /** @use HasFactory<PolicyTravelDetailsFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'trip_start_date' => 'date',
            'trip_end_date' => 'date',
        ];
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }
}
