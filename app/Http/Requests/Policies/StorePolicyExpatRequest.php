<?php

declare(strict_types=1);

namespace App\Http\Requests\Policies;

use App\Concerns\PolicyValidationRules;
use App\Enums\ExpatCoverageZone;
use App\Enums\Gender;
use App\Enums\PolicyClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class StorePolicyExpatRequest extends FormRequest
{
    use PolicyValidationRules;

    protected function prepareForValidation(): void
    {
        $this->merge([
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
            'expat.date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'expat.phone' => ['required', 'string', 'max:30'],
            'expat.country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'expat.visa_expiry_date' => ['nullable', 'date'],
        ];
    }

    /**
     * Get the readable names of the fields, as the Expat policy form labels them.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            ...$this->policyAttributes(),

            'expat.coverage_zone' => 'coverage zone',
            'expat.travel_scope' => 'travel scope',
            'expat.full_name' => 'full name',
            'expat.gender' => 'gender',
            'expat.nationality' => 'nationality',
            'expat.date_of_birth' => 'date of birth',
            'expat.phone' => 'phone',
            'expat.country_id' => 'country',
            'expat.visa_expiry_date' => 'visa expiry date',
        ];
    }

    /**
     * Get the messages for conditional rules whose default wording would read awkwardly or expose a raw value.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'expat.travel_scope.required_if' => 'The :attribute field is required when the coverage zone is '.ExpatCoverageZone::InOut->label().'.',
            'expat.travel_scope.prohibited_unless' => 'The :attribute field is only allowed when the coverage zone is '.ExpatCoverageZone::InOut->label().'.',
        ];
    }
}
