<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\CarrierBranchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $carrier_id
 * @property string|null $street
 * @property string|null $building_floor
 * @property string|null $city
 * @property int|null $state_id
 * @property int|null $country_id
 * @property string $contact_name
 * @property string|null $contact_role
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Carrier $carrier
 * @property-read Country|null $country
 * @property-read State|null $state
 */
#[Fillable([
    'carrier_id', 'street', 'building_floor', 'city', 'state_id', 'country_id',
    'contact_name', 'contact_role', 'contact_email', 'contact_phone',
])]
final class CarrierBranch extends Model
{
    /** @use HasFactory<CarrierBranchFactory> */
    use HasFactory;

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }
}
