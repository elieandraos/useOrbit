<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Concerns\GeneratesUniqueSlug;
use App\Models\Policy;
use App\Models\User;
use App\Support\Tenancy\OrganizationContext;
use Illuminate\Support\Facades\DB;

final class CreatePolicyAction
{
    use GeneratesUniqueSlug;

    public function __construct(
        private readonly OrganizationContext $organizationContext,
    ) {}

    /**
     * @param  array{policy_number: string, class: string, subclass: string, type: string, client_id: string, carrier_id: string, agent_id?: string|null, currency_id: string, effective_date: string, expiry_date: string, premium_amount: string, discount_amount?: string|null, status: string, source: string}  $attributes
     *
     * @throws \Throwable
     */
    public function handle(User $user, array $attributes): Policy
    {
        return DB::transaction(function () use ($user, $attributes): Policy {
            $organizationId = $this->organizationContext->id();

            $slug = $this->generateUniqueSlug(Policy::class, $attributes['policy_number'], $organizationId);

            /** @var Policy $policy */
            $policy = Policy::query()->create([
                'organization_id' => $organizationId,
                'slug' => $slug,
                'policy_number' => $attributes['policy_number'],
                'class' => $attributes['class'],
                'subclass' => $attributes['subclass'],
                'type' => $attributes['type'],
                'client_id' => $attributes['client_id'],
                'carrier_id' => $attributes['carrier_id'],
                'agent_id' => $attributes['agent_id'] ?? null,
                'currency_id' => $attributes['currency_id'],
                'effective_date' => $attributes['effective_date'],
                'expiry_date' => $attributes['expiry_date'],
                'premium_amount' => $attributes['premium_amount'],
                'discount_amount' => $attributes['discount_amount'] ?? 0,
                'status' => $attributes['status'],
                'source' => $attributes['source'],
                'created_by' => $user->id,
            ]);

            return $policy;
        });
    }
}
