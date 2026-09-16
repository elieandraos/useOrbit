<?php

declare(strict_types=1);

namespace App\Http\Requests\Policies;

use App\Enums\ExpatCoverageZone;
use App\Enums\Gender;
use App\Enums\PolicyClass;
use App\Enums\PolicySource;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class UpdatePolicyExpatRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->input('status') ?? PolicyStatus::Active->value,
        ]);
    }

    public function rules(): array
    {
        $organizationId = $this->user()?->organization_id;
        $isInOut = $this->input('expat.coverage_zone') === ExpatCoverageZone::InOut->value;

        return [
            'policy_number' => ['nullable', 'string', 'max:50'],
            'class' => ['required', Rule::in([PolicyClass::Expat->value])],
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

            'expat.coverage_zone' => ['required', new Enum(ExpatCoverageZone::class)],
            'expat.travel_scope' => [Rule::requiredIf($isInOut), Rule::prohibitedIf(! $isInOut), 'nullable', 'string', 'max:100'],
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
