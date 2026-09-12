<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExpatCoverageZone;
use App\Enums\Gender;
use Carbon\CarbonImmutable;
use Database\Factories\PolicyExpatDetailsFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $policy_id
 * @property ExpatCoverageZone $coverage_zone
 * @property string|null $travel_scope
 * @property string $full_name
 * @property Gender $gender
 * @property string $nationality
 * @property CarbonImmutable $date_of_birth
 * @property string $phone
 * @property int|null $country_id
 * @property CarbonImmutable|null $visa_expiry_date
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Policy $policy
 * @property-read Country|null $country
 */
#[Fillable([
    'policy_id', 'coverage_zone', 'travel_scope', 'full_name', 'gender', 'nationality',
    'date_of_birth', 'phone', 'country_id', 'visa_expiry_date',
])]
final class PolicyExpatDetails extends Model
{
    /** @use HasFactory<PolicyExpatDetailsFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'coverage_zone' => ExpatCoverageZone::class,
            'gender' => Gender::class,
            'date_of_birth' => 'date',
            'visa_expiry_date' => 'date',
        ];
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
