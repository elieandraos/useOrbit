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
        $countryName = $this->relationLoaded('country') ? $this->country?->name : null;

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'full_name' => "$this->first_name $this->last_name",
            'mothers_name' => $this->mothers_name,
            'date_of_birth' => $this->date_of_birth->format('Y-m-d'),
            'date_of_birth_formatted' => $this->date_of_birth->format('M j, Y'),
            'age' => $this->date_of_birth->age,
            'gender' => $this->gender,
            'gender_label' => $this->gender->label(),
            'photo' => $this->photo,
            'phone' => $this->phone,
            'email' => $this->email,
            'street' => $this->street,
            'building_floor' => $this->building_floor,
            'city' => $this->city,
            'state' => $this->state,
            'country_id' => $this->country_id,
            'full_address' => collect([$this->street, $this->building_floor, $this->city, $this->state, $countryName])
                ->filter()
                ->implode(', '),
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_relationship' => $this->emergency_contact_relationship,
            'emergency_contact_relationship_label' => $this->emergency_contact_relationship?->label(),
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'enrollment_date' => $this->enrollment_date->format('Y-m-d'),
            'enrollment_date_formatted' => $this->enrollment_date->format('M j, Y'),
            'lead_source' => $this->lead_source,
            'lead_source_label' => $this->lead_source->label(),
            'status' => $this->status,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at->format('M j, Y · g:i A'),
            'updated_at' => $this->updated_at->format('M j, Y · g:i A'),
            'updated_by_name' => $this->whenLoaded('updatedBy', fn () => $this->updatedBy?->name),
        ];
    }
}
