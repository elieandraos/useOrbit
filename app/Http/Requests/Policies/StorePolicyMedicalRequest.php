<?php

declare(strict_types=1);

namespace App\Http\Requests\Policies;

use App\Concerns\PolicyValidationRules;
use App\Enums\Gender;
use App\Enums\MedicalClassTier;
use App\Enums\MedicalCoverageScope;
use App\Enums\PolicyClass;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class StorePolicyMedicalRequest extends FormRequest
{
    use PolicyValidationRules;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->input('status') ?? PolicyStatus::Active->value,
            'medical' => [
                ...$this->input('medical', []),
                'co_insurance' => $this->boolean('medical.co_insurance'),
                'insured_smoker' => $this->has('medical.insured_smoker') ? $this->boolean('medical.insured_smoker') : null,
            ],
        ]);
    }

    public function rules(): array
    {
        return [
            ...$this->policyRules(PolicyClass::Medical),

            'medical.coverage_scope' => ['required', new Enum(MedicalCoverageScope::class)],
            'medical.class_tier' => ['required', new Enum(MedicalClassTier::class)],
            'medical.co_insurance' => ['required', 'boolean'],
            'medical.co_insurance_share' => ['required_if_accepted:medical.co_insurance', 'prohibited_if_declined:medical.co_insurance', 'nullable', 'numeric', 'between:0,100'],
            'medical.guaranteed_renewable' => ['required', 'boolean'],

            'medical.insured_full_name' => ['required_if:type,'.PolicyType::Single->value, 'prohibited_unless:type,'.PolicyType::Single->value, 'nullable', 'string', 'max:255'],
            'medical.insured_date_of_birth' => ['required_if:type,'.PolicyType::Single->value, 'prohibited_unless:type,'.PolicyType::Single->value, 'nullable', 'date', 'before_or_equal:today'],
            'medical.insured_gender' => ['required_if:type,'.PolicyType::Single->value, 'prohibited_unless:type,'.PolicyType::Single->value, 'nullable', new Enum(Gender::class)],
            'medical.insured_smoker' => ['required_if:type,'.PolicyType::Single->value, 'prohibited_unless:type,'.PolicyType::Single->value, 'nullable', 'boolean'],
            'medical.insured_medical_history' => ['nullable', 'string'],

            'insureds' => ['required_if:type,'.PolicyType::Group->value, 'prohibited_unless:type,'.PolicyType::Group->value, 'array', 'min:1'],
            'insureds.*.full_name' => ['required', 'string', 'max:255'],
            'insureds.*.relationship' => ['required', 'string', 'max:20'],
            'insureds.*.date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'insureds.*.gender' => ['nullable', new Enum(Gender::class)],
            'insureds.*.medical_notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Get the readable names of the fields, as the Medical policy form labels them.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            ...$this->policyAttributes(),

            'medical.coverage_scope' => 'coverage scope',
            'medical.class_tier' => 'plan tier',
            'medical.co_insurance' => 'co-insurance',
            'medical.co_insurance_share' => 'co-insurance share',
            'medical.guaranteed_renewable' => 'guaranteed renewable',
            'medical.insured_full_name' => 'insured full name',
            'medical.insured_date_of_birth' => 'insured date of birth',
            'medical.insured_gender' => 'insured gender',
            'medical.insured_smoker' => 'insured smoker',
            'medical.insured_medical_history' => 'insured medical history',
            'insureds' => 'covered members',
            'insureds.*.full_name' => 'member :position full name',
            'insureds.*.relationship' => 'member :position relationship',
            'insureds.*.date_of_birth' => 'member :position date of birth',
            'insureds.*.gender' => 'member :position gender',
            'insureds.*.medical_notes' => 'member :position medical notes',
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
            'medical.co_insurance_share.required_if_accepted' => 'The :attribute field is required when co-insurance applies.',
            'medical.co_insurance_share.prohibited_if_declined' => 'The :attribute field is only allowed when co-insurance applies.',
            'medical.insured_*.required_if' => 'The :attribute field is required on a '.PolicyType::Single->label().' policy.',
            'medical.insured_*.prohibited_unless' => 'The :attribute field is only allowed on a '.PolicyType::Single->label().' policy.',
            'insureds.required_if' => 'Add at least one covered member to a '.PolicyType::Group->label().' policy.',
            'insureds.min' => 'Add at least one covered member to a '.PolicyType::Group->label().' policy.',
            'insureds.prohibited_unless' => 'Covered members are only allowed on a '.PolicyType::Group->label().' policy.',
        ];
    }
}
