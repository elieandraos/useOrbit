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
            'id' => $this->id,
            'slug' => $this->slug,
            'policy_number' => $this->policy_number,
            'class' => $this->class,
            'class_label' => $this->class->label(),
            'subclass' => $this->subclass,
            'type' => $this->type,
            'type_label' => $this->type->label(),
            'client' => $this->whenLoaded('client', fn () => [
                'id' => $this->client->id,
                'slug' => $this->client->slug,
                'full_name' => $this->client->full_name,
            ]),
            'carrier' => $this->whenLoaded('carrier', fn () => [
                'id' => $this->carrier->id,
                'slug' => $this->carrier->slug,
                'name' => $this->carrier->name,
            ]),
            'agent' => $this->whenLoaded('agent', fn () => $this->agent === null ? null : [
                'id' => $this->agent->id,
                'slug' => $this->agent->slug,
                'full_name' => $this->agent->full_name,
            ]),
            'effective_date' => $this->effective_date->format('Y-m-d'),
            'effective_date_formatted' => $this->effective_date->format('M j, Y'),
            'expiry_date' => $this->expiry_date->format('Y-m-d'),
            'expiry_date_formatted' => $this->expiry_date->format('M j, Y'),
            'premium_amount' => $this->premium_amount,
            'discount_amount' => $this->discount_amount,
            'net_premium' => number_format((float) $this->premium_amount - (float) $this->discount_amount, 2, '.', ''),
            'status' => $this->status,
            'status_label' => $this->status->label(),
            'source' => $this->source,
            'source_label' => $this->source->label(),
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
