<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Enums\PolicyClass;
use App\Enums\PolicySource;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use App\Models\Policy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * @mixin FormRequest
 */
trait PolicyValidationRules
{
    /**
     * Get the validation rules shared by every policy, whatever its class.
     *
     * @return array<string, array<int, ValidationRule|array|string>>
     */
    protected function policyRules(PolicyClass $policyClass): array
    {
        $organizationId = $this->user()?->organization_id;

        /** @var Policy|null $policy */
        $policy = $this->route('policy');

        return [
            'policy_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('policies', 'policy_number')
                    ->where('organization_id', $organizationId)
                    ->ignore($policy?->id),
            ],
            'class' => ['required', Rule::in([$policyClass->value])],
            'subclass' => ['required', 'string', Rule::in($policyClass->subclasses())],
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
        ];
    }
}
