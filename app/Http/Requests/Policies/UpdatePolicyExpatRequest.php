<?php

declare(strict_types=1);

namespace App\Http\Requests\Policies;

use App\Concerns\PolicyValidationRules;
use App\Enums\ExpatCoverageZone;
use App\Enums\Gender;
use App\Enums\PolicyClass;
use App\Enums\PolicyStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class UpdatePolicyExpatRequest extends FormRequest
{
    use PolicyValidationRules;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->input('status') ?? PolicyStatus::Active->value,
        ]);
    }

    public function rules(): array
    {
        return [
            ...$this->policyRules(PolicyClass::Expat),

            'expat.coverage_zone' => ['required', new Enum(ExpatCoverageZone::class)],
            'expat.travel_scope' => ['required_if:expat.coverage_zone,'.ExpatCoverageZone::InOut->value, 'prohibited_unless:expat.coverage_zone,'.ExpatCoverageZone::InOut->value, 'nullable', 'string', 'max:100'],
            'expat.full_name' => ['required', 'string', 'max:255'],
            'expat.gender' => ['required', new Enum(Gender::class)],
            'expat.nationality' => ['required', 'string', 'max:100'],
            'expat.date_of_birth' => ['required', 'date'],
            'expat.phone' => ['required', 'string', 'max:30'],
            'expat.country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'expat.visa_expiry_date' => ['nullable', 'date'],
        ];
    }
}
