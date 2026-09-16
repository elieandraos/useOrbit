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
