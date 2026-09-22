<?php

declare(strict_types=1);

namespace App\Http\Requests\Policies;

use App\Enums\Gender;
use App\Enums\MedicalClassTier;
use App\Enums\MedicalCoverageScope;
use App\Enums\PolicyClass;
use App\Enums\PolicySource;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class StorePolicyMedicalRequest extends FormRequest
{
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
        $organizationId = $this->user()?->organization_id;
        $isSingle = $this->input('type') === PolicyType::Single->value;
        $isGroup = $this->input('type') === PolicyType::Group->value;
        $coInsurance = $this->boolean('medical.co_insurance');

        return [
            'policy_number' => ['nullable', 'string', 'max:50'],
            'class' => ['required', Rule::in([PolicyClass::Medical->value])],
            'subclass' => ['required', 'string', 'max:50'],
            'type' => ['required', new Enum(PolicyType::class)],
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')->where('organization_id', $organizationId)],
            'carrier_id' => ['required', 'integer', Rule::exists('carriers', 'id')->where('organization_id', $organizationId)],
            'agent_id' => ['nullable', 'integer', Rule::exists('agents', 'id')->where('organization_id', $organizationId)],
            'effective_date' => ['required', 'date'],
            'expiry_date' => ['required', 'date', 'after_or_equal:effective_date'],
            'premium_amount' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', new Enum(PolicyStatus::class)],
            'source' => ['required', new Enum(PolicySource::class)],

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
            'insureds.*.full_name' => ['required', 'string', 'max:255'],
            'insureds.*.relationship' => ['required', 'string', 'max:20'],
            'insureds.*.date_of_birth' => ['required', 'date'],
            'insureds.*.gender' => ['nullable', new Enum(Gender::class)],
            'insureds.*.medical_notes' => ['nullable', 'string'],
        ];
    }
}
