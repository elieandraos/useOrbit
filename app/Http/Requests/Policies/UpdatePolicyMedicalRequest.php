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
use App\Models\Policy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class UpdatePolicyMedicalRequest extends FormRequest
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
        /** @var Policy $policy */
        $policy = $this->route('policy');

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
            'insureds.*.id' => ['nullable', 'integer', Rule::exists('policy_insureds', 'id')->where('policy_id', $policy->id)],
            'insureds.*.full_name' => ['required', 'string', 'max:255'],
            'insureds.*.relationship' => ['required', 'string', 'max:20'],
            'insureds.*.date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'insureds.*.gender' => ['nullable', new Enum(Gender::class)],
            'insureds.*.medical_notes' => ['nullable', 'string'],
        ];
    }
}
