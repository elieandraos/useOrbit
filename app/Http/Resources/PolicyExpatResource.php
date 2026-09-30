<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Policy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Policy */
final class PolicyExpatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            ...PolicyResource::make($this->resource)->baseAttributes(),
            'details' => $this->whenLoaded('expatDetails', fn () => [
                'id' => $this->expatDetails->id,
                'coverage_zone' => $this->expatDetails->coverage_zone,
                'coverage_zone_label' => $this->expatDetails->coverage_zone->label(),
                'travel_scope' => $this->expatDetails->travel_scope,
                'full_name' => $this->expatDetails->full_name,
                'gender' => $this->expatDetails->gender,
                'gender_label' => $this->expatDetails->gender->label(),
                'nationality' => $this->expatDetails->nationality,
                'date_of_birth' => $this->expatDetails->date_of_birth->format('Y-m-d'),
                'date_of_birth_formatted' => $this->expatDetails->date_of_birth->format('M j, Y'),
                'phone' => $this->expatDetails->phone,
                'country_id' => $this->expatDetails->country_id,
                'country_name' => $this->expatDetails->relationLoaded('country') ? $this->expatDetails->country?->name : null,
                'visa_expiry_date' => $this->expatDetails->visa_expiry_date?->format('Y-m-d'),
                'visa_expiry_date_formatted' => $this->expatDetails->visa_expiry_date?->format('M j, Y'),
            ]),
        ];
    }
}
