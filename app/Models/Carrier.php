<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CarrierStatus;
use App\Models\Concerns\BelongsToCurrentOrganization;
use App\Models\Concerns\HasSlug;
use Carbon\CarbonImmutable;
use Database\Factories\CarrierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $slug
 * @property string $name
 * @property string|null $phone
 * @property string|null $website
 * @property CarbonImmutable $onboarded_date
 * @property CarrierStatus $status
 * @property int $created_by
 * @property int|null $updated_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read User $createdBy
 * @property-read User|null $updatedBy
 * @property-read Collection<int, CarrierBranch> $branches
 * @property-read CarrierBranch|null $hqBranch
 */
#[Fillable([
    'organization_id', 'slug', 'name', 'phone', 'website', 'onboarded_date', 'status', 'created_by', 'updated_by',
])]
final class Carrier extends Model
{
    /** @use HasFactory<CarrierFactory> */
    use BelongsToCurrentOrganization, HasFactory, HasSlug, SoftDeletes;

    protected function casts(): array
    {
        return [
            'onboarded_date' => 'date',
            'status' => CarrierStatus::class,
        ];
    }

    public function branches(): HasMany
    {
        return $this->hasMany(CarrierBranch::class);
    }

    public function hqBranch(): HasOne
    {
        return $this->hasOne(CarrierBranch::class)->where('is_hq', true);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
