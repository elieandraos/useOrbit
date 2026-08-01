<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ClientStatus;
use App\Enums\ClientType;
use App\Enums\EmergencyContactRelationship;
use App\Enums\Gender;
use App\Enums\LeadSource;
use App\Models\Concerns\BelongsToCurrentOrganization;
use App\Models\Concerns\Filterable;
use App\Models\Concerns\HasDocuments;
use App\Models\Concerns\HasNotes;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\Sortable;
use App\Models\Contracts\Documentable;
use App\Models\Contracts\Notable;
use Carbon\CarbonImmutable;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $slug
 * @property ClientType $client_type
 * @property string|null $company_name
 * @property string $first_name
 * @property string|null $middle_name
 * @property string $last_name
 * @property string|null $mothers_name
 * @property CarbonImmutable|null $date_of_birth
 * @property Gender|null $gender
 * @property string|null $photo
 * @property string $phone
 * @property string|null $email
 * @property string|null $street
 * @property string|null $building_floor
 * @property int|null $country_id
 * @property int|null $state_id
 * @property string|null $city
 * @property string|null $emergency_contact_name
 * @property EmergencyContactRelationship|null $emergency_contact_relationship
 * @property string|null $emergency_contact_phone
 * @property CarbonImmutable $enrollment_date
 * @property LeadSource $lead_source
 * @property ClientStatus $status
 * @property int $created_by
 * @property int|null $updated_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read User|null $updatedBy
 * @property-read Country|null $country
 * @property-read State|null $state
 *
 * When client_type is Company, first_name/last_name/phone/email hold the contact person's info, not the client's own.
 */
#[Fillable([
    'organization_id', 'slug', 'client_type', 'company_name', 'first_name', 'middle_name', 'last_name', 'mothers_name',
    'date_of_birth', 'gender', 'photo', 'phone', 'email', 'street', 'building_floor',
    'country_id', 'state_id', 'city', 'emergency_contact_name', 'emergency_contact_relationship',
    'emergency_contact_phone', 'enrollment_date', 'lead_source', 'status', 'created_by', 'updated_by',
])]
final class Client extends Model implements Documentable, Notable
{
    /** @use HasFactory<ClientFactory> */
    use BelongsToCurrentOrganization, Filterable, HasDocuments, HasFactory, HasNotes, HasSlug, SoftDeletes, Sortable;

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'enrollment_date' => 'date',
            'client_type' => ClientType::class,
            'emergency_contact_relationship' => EmergencyContactRelationship::class,
            'gender' => Gender::class,
            'lead_source' => LeadSource::class,
            'status' => ClientStatus::class,
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
        return 'client';
    }

    public function documentableName(): string
    {
        return $this->client_type === ClientType::Company
            ? (string) $this->company_name
            : "$this->first_name $this->last_name";
    }
}
