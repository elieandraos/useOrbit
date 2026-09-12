<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Agent */
final class AgentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $countryName = $this->relationLoaded('country') ? $this->country?->name : null;
        $stateName = $this->relationLoaded('state') ? $this->state?->name : null;

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'date_of_birth' => $this->date_of_birth->format('Y-m-d'),
            'date_of_birth_formatted' => $this->date_of_birth->format('M j, Y'),
            'age' => $this->date_of_birth->age,
            'joined_at' => $this->joined_at->format('Y-m-d'),
            'joined_at_formatted' => $this->joined_at->format('M j, Y'),
            'tenure' => $this->tenure(),
            'phone' => $this->phone,
            'email' => $this->email,
            'street' => $this->street,
            'building_floor' => $this->building_floor,
            'country_id' => $this->country_id,
            'state_id' => $this->state_id,
            'city' => $this->city,
            'country_name' => $countryName,
            'state_name' => $stateName,
            'full_address' => collect([$this->street, $this->building_floor, $this->city, $stateName, $countryName])
                ->filter()
                ->implode("\n"),
            'status' => $this->status,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at->format('M j, Y · g:i A'),
            'updated_at' => $this->updated_at->format('M j, Y · g:i A'),
            'updated_by_name' => $this->whenLoaded('updatedBy', fn () => $this->updatedBy?->name),
        ];
    }

    private function tenure(): string
    {
        $diff = $this->joined_at->diff(now());

        return "{$diff->y}y {$diff->m}m";
    }
}
