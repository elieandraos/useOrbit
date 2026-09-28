<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Policy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Policy */
final class PolicyMedicalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            ...PolicyResource::make($this->resource)->baseAttributes(),
            'details' => $this->whenLoaded('medicalDetails', fn () => PolicyMedicalDetailsResource::make($this->medicalDetails)),
            'insureds' => PolicyInsuredResource::collection($this->whenLoaded('insureds')),
        ];
    }
}
