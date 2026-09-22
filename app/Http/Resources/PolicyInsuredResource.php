<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\PolicyInsured;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PolicyInsured */
final class PolicyInsuredResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'member_code' => $this->member_code,
            'full_name' => $this->full_name,
            'relationship' => $this->relationship,
            'date_of_birth' => $this->date_of_birth->format('Y-m-d'),
            'date_of_birth_formatted' => $this->date_of_birth->format('M j, Y'),
            'age' => $this->date_of_birth->age,
            'gender' => $this->gender,
            'gender_label' => $this->gender?->label(),
            'medical_notes' => $this->medical_notes,
            'status' => $this->status,
        ];
    }
}
