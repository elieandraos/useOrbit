<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Policy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Policy */
final class PolicyFireResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            ...PolicyResource::make($this->resource)->baseAttributes(),
            'details' => $this->whenLoaded('fireDetails', fn () => [
                'id' => $this->fireDetails->id,
                'property_type' => $this->fireDetails->property_type,
                'floor_area' => $this->fireDetails->floor_area,
                'year_built' => $this->fireDetails->year_built,
                'street' => $this->fireDetails->street,
                'building_floor' => $this->fireDetails->building_floor,
                'city' => $this->fireDetails->city,
                'state_id' => $this->fireDetails->state_id,
                'state_name' => $this->fireDetails->relationLoaded('state') ? $this->fireDetails->state?->name : null,
                'country_id' => $this->fireDetails->country_id,
                'country_name' => $this->fireDetails->relationLoaded('country') ? $this->fireDetails->country?->name : null,
                'sum_insured' => $this->fireDetails->sum_insured,
            ]),
        ];
    }
}
