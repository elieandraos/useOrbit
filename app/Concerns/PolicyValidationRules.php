<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Enums\AgentStatus;
use App\Enums\CarrierStatus;
use App\Enums\ClientStatus;
use App\Enums\PolicyClass;
use App\Enums\PolicySource;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use App\Models\Policy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Exists;

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
            'client_id' => ['required', 'integer', $this->assignablePartyRule('clients', $organizationId, ClientStatus::Active->value, $policy?->client_id)],
            'carrier_id' => ['required', 'integer', $this->assignablePartyRule('carriers', $organizationId, CarrierStatus::Active->value, $policy?->carrier_id)],
            'agent_id' => ['nullable', 'integer', $this->assignablePartyRule('agents', $organizationId, AgentStatus::Active->value, $policy?->agent_id)],
            'effective_date' => ['required', 'date'],
            'expiry_date' => ['required', 'date', 'after_or_equal:effective_date'],
            'premium_amount' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0', 'lte:premium_amount'],
            'status' => ['required', new Enum(PolicyStatus::class)],
            'source' => ['required', new Enum(PolicySource::class)],
        ];
    }

    /**
     * An organization's party that is active, or the one the edited policy already holds even if it has since been archived.
     */
    private function assignablePartyRule(string $table, ?int $organizationId, string $activeStatus, ?int $currentPartyId): Exists
    {
        return Rule::exists($table, 'id')
            ->where('organization_id', $organizationId)
            ->where(fn (Builder $query): Builder => $query
                ->where('status', $activeStatus)
                ->when($currentPartyId !== null, fn (Builder $query): Builder => $query->orWhere('id', $currentPartyId))
            );
    }
}
