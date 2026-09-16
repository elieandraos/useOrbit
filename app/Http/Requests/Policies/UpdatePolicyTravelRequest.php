<?php

declare(strict_types=1);

namespace App\Http\Requests\Policies;

use App\Enums\PolicyClass;
use App\Enums\PolicySource;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class UpdatePolicyTravelRequest extends FormRequest
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

        return [
            'policy_number' => ['nullable', 'string', 'max:50'],
            'class' => ['required', Rule::in([PolicyClass::Travel->value])],
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

            'travel.destination' => ['required', 'string', 'max:255'],
            'travel.trip_start_date' => ['required', 'date'],
            'travel.trip_end_date' => ['required', 'date', 'after_or_equal:travel.trip_start_date'],
            'travel.travelers' => ['required', 'string'],
            'travel.coverage_tier' => ['required', 'string', 'max:20'],
        ];
    }
}
