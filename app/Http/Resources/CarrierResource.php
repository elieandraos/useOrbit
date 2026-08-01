<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Carrier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Carrier */
final class CarrierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'phone' => $this->phone,
            'website' => $this->website,
            'onboarded_date' => $this->onboarded_date->format('Y-m-d'),
            'onboarded_date_formatted' => $this->onboarded_date->format('M j, Y'),
            'status' => $this->status,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at->format('M j, Y · g:i A'),
            'updated_at' => $this->updated_at->format('M j, Y · g:i A'),
            'updated_by_name' => $this->whenLoaded('updatedBy', fn () => $this->updatedBy?->name),
            'branch' => $this->whenLoaded('hqBranch', function () {
                if ($this->hqBranch === null) {
                    return null;
                }

                $stateName = $this->hqBranch->relationLoaded('state') ? $this->hqBranch->state?->name : null;
                $countryName = $this->hqBranch->relationLoaded('country') ? $this->hqBranch->country?->name : null;

                return [
                    'id' => $this->hqBranch->id,
                    'street' => $this->hqBranch->street,
                    'building_floor' => $this->hqBranch->building_floor,
                    'city' => $this->hqBranch->city,
                    'state_id' => $this->hqBranch->state_id,
                    'country_id' => $this->hqBranch->country_id,
                    'state_name' => $stateName,
                    'country_name' => $countryName,
                    'phone' => $this->hqBranch->phone,
                    'contact_name' => $this->hqBranch->contact_name,
                    'contact_role' => $this->hqBranch->contact_role,
                    'contact_email' => $this->hqBranch->contact_email,
                    'contact_phone' => $this->hqBranch->contact_phone,
                    'contact_department' => $this->hqBranch->contact_department,
                ];
            }),
        ];
    }
}
