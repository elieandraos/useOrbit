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
        $isSingle = $this->input('type') === PolicyType::Single->value;
        $isGroup = $this->input('type') === PolicyType::Group->value;
        $coInsurance = $this->boolean('medical.co_insurance');

        return [
            ...$this->policyRules(PolicyClass::Medical),

            'medical.coverage_scope' => ['required', new Enum(MedicalCoverageScope::class)],
            'medical.class_tier' => ['required', new Enum(MedicalClassTier::class)],
            'medical.co_insurance' => ['required', 'boolean'],
            'medical.co_insurance_share' => [Rule::requiredIf($coInsurance), Rule::prohibitedIf(! $coInsurance), 'nullable', 'numeric', 'between:0,100'],
            'medical.guaranteed_renewable' => ['required', 'boolean'],

            'medical.insured_full_name' => [Rule::requiredIf($isSingle), Rule::prohibitedIf(! $isSingle), 'nullable', 'string', 'max:255'],
            'medical.insured_date_of_birth' => [Rule::requiredIf($isSingle), Rule::prohibitedIf(! $isSingle), 'nullable', 'date'],
            'medical.insured_gender' => [Rule::requiredIf($isSingle), Rule::prohibitedIf(! $isSingle), 'nullable', new Enum(Gender::class)],
            'medical.insured_smoker' => [Rule::requiredIf($isSingle), Rule::prohibitedIf(! $isSingle), 'nullable', 'boolean'],
            'medical.insured_medical_history' => ['nullable', 'string'],

            'insureds' => [Rule::requiredIf($isGroup), Rule::prohibitedIf(! $isGroup), 'array'],
            'insureds.*.id' => ['nullable', 'integer', Rule::exists('policy_insureds', 'id')->where('policy_id', $policy->id)],
            'insureds.*.full_name' => ['required', 'string', 'max:255'],
            'insureds.*.relationship' => ['required', 'string', 'max:20'],
            'insureds.*.date_of_birth' => ['required', 'date'],
            'insureds.*.gender' => ['nullable', new Enum(Gender::class)],
            'insureds.*.medical_notes' => ['nullable', 'string'],
        ];
    }
}
