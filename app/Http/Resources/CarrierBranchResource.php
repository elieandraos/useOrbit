<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\CarrierBranch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CarrierBranch */
final class CarrierBranchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'street' => $this->street,
            'building_floor' => $this->building_floor,
            'city' => $this->city,
            'state_id' => $this->state_id,
            'country_id' => $this->country_id,
            'state_name' => $this->whenLoaded('state', fn () => $this->state?->name),
            'country_name' => $this->whenLoaded('country', fn () => $this->country?->name),
            'contact_name' => $this->contact_name,
            'contact_role' => $this->contact_role,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
        ];
    }
}
