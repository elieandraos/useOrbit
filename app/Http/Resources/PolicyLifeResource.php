<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Policy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Policy */
final class PolicyLifeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            ...PolicyResource::make($this->resource)->baseAttributes(),
            'details' => $this->whenLoaded('lifeDetails', fn () => [
                'id' => $this->lifeDetails->id,
                'sum_assured' => $this->lifeDetails->sum_assured,
                'term_years' => $this->lifeDetails->term_years,
                'smoker' => $this->lifeDetails->smoker,
                'beneficiaries' => $this->lifeDetails->beneficiaries,
            ]),
        ];
    }
}
