<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Policy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Policy */
final class PolicyAutomotiveResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            ...PolicyResource::make($this->resource)->baseAttributes(),
            'details' => $this->whenLoaded('automotiveDetails', fn () => [
                'id' => $this->automotiveDetails->id,
                'plate_number' => $this->automotiveDetails->plate_number,
                'make' => $this->automotiveDetails->make,
                'model' => $this->automotiveDetails->model,
                'year' => $this->automotiveDetails->year,
                'vin' => $this->automotiveDetails->vin,
                'color' => $this->automotiveDetails->color,
                'valuation_amount' => $this->automotiveDetails->valuation_amount,
                'valuation_source' => $this->automotiveDetails->valuation_source,
            ]),
        ];
    }
}
