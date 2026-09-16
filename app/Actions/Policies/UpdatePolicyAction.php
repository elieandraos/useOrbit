<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Concerns\GeneratesUniqueSlug;
use App\Models\Policy;
use App\Models\User;
use App\Support\Tenancy\OrganizationContext;
use Illuminate\Support\Facades\DB;

final class UpdatePolicyAction
{
    use GeneratesUniqueSlug;

    public function __construct(
        private readonly OrganizationContext $organizationContext,
    ) {}

    /**
     * @param  array{policy_number?: string|null, class: string, subclass: string, type: string, client_id: int, carrier_id: int, agent_id?: int|null, effective_date: string, expiry_date: string, premium_amount: string, discount_amount?: string|null, status: string, source: string}  $attributes
     *
     * @throws \Throwable
     */
    public function handle(User $user, Policy $policy, array $attributes): Policy
    {
        return DB::transaction(function () use ($user, $policy, $attributes): Policy {
            $policyNumber = ! empty($attributes['policy_number'])
                ? $attributes['policy_number']
                : $policy->policy_number;

            $numberChanged = $policyNumber !== $policy->policy_number;

            $slug = $numberChanged
                ? $this->generateUniqueSlug(Policy::class, $policyNumber, $this->organizationContext->id(), $policy->id)
                : $policy->slug;

            $policy->update([
                'slug' => $slug,
                'policy_number' => $policyNumber,
                'class' => $attributes['class'],
                'subclass' => $attributes['subclass'],
                'type' => $attributes['type'],
                'client_id' => $attributes['client_id'],
                'carrier_id' => $attributes['carrier_id'],
                'agent_id' => $attributes['agent_id'] ?? null,
                'effective_date' => $attributes['effective_date'],
                'expiry_date' => $attributes['expiry_date'],
                'premium_amount' => $attributes['premium_amount'],
                'discount_amount' => $attributes['discount_amount'] ?? 0,
                'status' => $attributes['status'],
                'source' => $attributes['source'],
                'updated_by' => $user->id,
            ]);

            return $policy->fresh();
        });
    }
}
