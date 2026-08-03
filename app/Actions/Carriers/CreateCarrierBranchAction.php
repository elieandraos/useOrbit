<?php

declare(strict_types=1);

namespace App\Actions\Carriers;

use App\Models\Carrier;
use App\Models\CarrierBranch;

final class CreateCarrierBranchAction
{
    /**
     * @param  array{street?: string|null, building_floor?: string|null, city: string, country_id?: int|null, state_id?: int|null, contact_name: string, contact_role?: string|null, contact_email?: string|null, contact_phone?: string|null}  $attributes
     */
    public function handle(Carrier $carrier, array $attributes): CarrierBranch
    {
        return $carrier->branches()->create([
            'street' => $attributes['street'] ?? null,
            'building_floor' => $attributes['building_floor'] ?? null,
            'city' => $attributes['city'],
            'state_id' => $attributes['state_id'] ?? null,
            'country_id' => $attributes['country_id'] ?? null,
            'contact_name' => $attributes['contact_name'],
            'contact_role' => $attributes['contact_role'] ?? null,
            'contact_email' => $attributes['contact_email'] ?? null,
            'contact_phone' => $attributes['contact_phone'] ?? null,
        ]);
    }
}
