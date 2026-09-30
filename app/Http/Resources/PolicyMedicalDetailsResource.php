<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\PolicyMedicalDetails;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PolicyMedicalDetails */
final class PolicyMedicalDetailsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'coverage_scope' => $this->coverage_scope,
            'coverage_scope_label' => $this->coverage_scope->label(),
            'class_tier' => $this->class_tier,
            'class_tier_label' => $this->class_tier->label(),
            'co_insurance' => $this->co_insurance,
            'co_insurance_share' => $this->co_insurance_share,
            'guaranteed_renewable' => $this->guaranteed_renewable,
            'insured_full_name' => $this->insured_full_name,
            'insured_date_of_birth' => $this->insured_date_of_birth?->format('Y-m-d'),
            'insured_date_of_birth_formatted' => $this->insured_date_of_birth?->format('M j, Y'),
            'insured_gender' => $this->insured_gender,
            'insured_gender_label' => $this->insured_gender?->label(),
            'insured_smoker' => $this->insured_smoker,
            'insured_medical_history' => $this->insured_medical_history,
        ];
    }
}
