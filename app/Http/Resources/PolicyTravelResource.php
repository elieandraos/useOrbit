<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Policy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Policy */
final class PolicyTravelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            ...PolicyResource::make($this->resource)->baseAttributes(),
            'details' => $this->whenLoaded('travelDetails', fn () => [
                'id' => $this->travelDetails->id,
                'destination' => $this->travelDetails->destination,
                'trip_start_date' => $this->travelDetails->trip_start_date->format('Y-m-d'),
                'trip_start_date_formatted' => $this->travelDetails->trip_start_date->format('M j, Y'),
                'trip_end_date' => $this->travelDetails->trip_end_date->format('Y-m-d'),
                'trip_end_date_formatted' => $this->travelDetails->trip_end_date->format('M j, Y'),
                'travelers' => $this->travelDetails->travelers,
                'coverage_tier' => $this->travelDetails->coverage_tier,
                'coverage_tier_label' => $this->travelDetails->coverage_tier->label(),
            ]),
        ];
    }
}
