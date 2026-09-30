<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AgentStatus;
use App\Models\Concerns\BelongsToCurrentOrganization;
use App\Models\Concerns\Filterable;
use App\Models\Concerns\HasFullAddress;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\Sortable;
use App\Models\Contracts\NotificationSubject;
use Carbon\CarbonImmutable;
use Database\Factories\AgentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $slug
 * @property string $first_name
 * @property string $last_name
 * @property CarbonImmutable $date_of_birth
 * @property CarbonImmutable $joined_at
 * @property string $phone
 * @property string $email
 * @property string|null $street
 * @property string|null $building_floor
 * @property string|null $city
 * @property int|null $state_id
 * @property int|null $country_id
 * @property AgentStatus $status
 * @property int $created_by
 * @property int|null $updated_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read User $createdBy
 * @property-read User|null $updatedBy
 * @property-read Country|null $country
 * @property-read State|null $state
 * @property-read string $full_name
 * @property-read string $full_address
 * @property-read Collection<int, Policy> $policies
 */
#[Fillable([
    'organization_id', 'slug', 'first_name', 'last_name', 'date_of_birth', 'joined_at', 'phone', 'email',
    'street', 'building_floor', 'city', 'state_id', 'country_id', 'status', 'created_by', 'updated_by',
])]
final class Agent extends Model implements NotificationSubject
{
    /** @use HasFactory<AgentFactory> */
    use BelongsToCurrentOrganization, Filterable, HasFactory, HasFullAddress, HasSlug, SoftDeletes, Sortable;

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'joined_at' => 'date',
            'status' => AgentStatus::class,
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function policies(): HasMany
    {
        return $this->hasMany(Policy::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => "$this->first_name $this->last_name",
        );
    }

    public function notificationSubjectKind(): string
    {
        return 'agent';
    }

    public function notificationSubjectName(): string
    {
        return $this->full_name;
    }
}
