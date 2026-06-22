<?php

namespace App\Models;

use App\Enums\ClientStatus;
use App\Enums\Gender;
use App\Enums\LeadSource;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'organization_id', 'slug', 'first_name', 'middle_name', 'last_name', 'mothers_name',
    'date_of_birth', 'gender', 'photo', 'phone', 'email', 'street', 'building_floor',
    'city', 'state', 'country_id', 'emergency_contact_name', 'emergency_contact_relationship',
    'emergency_contact_phone', 'enrollment_date', 'lead_source', 'status', 'created_by', 'updated_by',
])]
class Client extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'enrollment_date' => 'date',
            'gender' => Gender::class,
            'lead_source' => LeadSource::class,
            'status' => ClientStatus::class,
        ];
    }
}
