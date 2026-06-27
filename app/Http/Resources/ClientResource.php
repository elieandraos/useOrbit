<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Client */
final class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'mothers_name' => $this->mothers_name,
            'date_of_birth' => $this->date_of_birth,
            'gender' => $this->gender,
            'photo' => $this->photo,
            'phone' => $this->phone,
            'email' => $this->email,
            'street' => $this->street,
            'building_floor' => $this->building_floor,
            'city' => $this->city,
            'state' => $this->state,
            'country_id' => $this->country_id,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_relationship' => $this->emergency_contact_relationship,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'enrollment_date' => $this->enrollment_date,
            'lead_source' => $this->lead_source,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
        ];
    }
}
