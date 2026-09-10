<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PolicyClass;
use App\Enums\PolicySource;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use App\Models\Concerns\BelongsToCurrentOrganization;
use App\Models\Concerns\Filterable;
use App\Models\Concerns\HasDocuments;
use App\Models\Concerns\HasNotes;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\Sortable;
use App\Models\Contracts\Documentable;
use App\Models\Contracts\Notable;
use App\Models\Contracts\NotificationSubject;
use Carbon\CarbonImmutable;
use Database\Factories\PolicyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $slug
 * @property string $policy_number
 * @property PolicyClass $class
 * @property string $subclass
 * @property PolicyType $type
 * @property int $client_id
 * @property int $carrier_id
 * @property int|null $agent_id
 * @property CarbonImmutable $effective_date
 * @property CarbonImmutable $expiry_date
 * @property CarbonImmutable|null $bound_at
 * @property string $premium_amount
 * @property string $discount_amount
 * @property PolicyStatus $status
 * @property PolicySource $source
 * @property int $created_by
 * @property int|null $updated_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Client $client
 * @property-read Carrier $carrier
 * @property-read Agent|null $agent
 * @property-read User $createdBy
 * @property-read User|null $updatedBy
 * @property-read PolicyMedicalDetails|null $medicalDetails
 * @property-read PolicyAutomotiveDetails|null $automotiveDetails
 * @property-read PolicyExpatDetails|null $expatDetails
 */
#[Fillable([
    'organization_id', 'slug', 'policy_number', 'class', 'subclass', 'type', 'client_id', 'carrier_id',
    'agent_id', 'effective_date', 'expiry_date', 'bound_at', 'premium_amount', 'discount_amount',
    'status', 'source', 'created_by', 'updated_by',
])]
final class Policy extends Model implements Documentable, Notable, NotificationSubject
{
    /** @use HasFactory<PolicyFactory> */
    use BelongsToCurrentOrganization, Filterable, HasDocuments, HasFactory, HasNotes, HasSlug, SoftDeletes, Sortable;

    protected function casts(): array
    {
        return [
            'class' => PolicyClass::class,
            'type' => PolicyType::class,
            'effective_date' => 'date',
            'expiry_date' => 'date',
            'bound_at' => 'date',
            'premium_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'status' => PolicyStatus::class,
            'source' => PolicySource::class,
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function medicalDetails(): HasOne
    {
        return $this->hasOne(PolicyMedicalDetails::class);
    }

    public function automotiveDetails(): HasOne
    {
        return $this->hasOne(PolicyAutomotiveDetails::class);
    }

    public function expatDetails(): HasOne
    {
        return $this->hasOne(PolicyExpatDetails::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function documentableKind(): string
    {
        return 'policy';
    }

    public function documentableName(): string
    {
        return $this->policy_number;
    }

    public function notificationSubjectKind(): string
    {
        return 'policy';
    }

    public function notificationSubjectName(): string
    {
        return $this->policy_number;
    }
}
