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
     * @param  array{policy_number?: string|null, class: string, subclass: string, type: string, client_id: int, carrier_id: int, agent_id?: int|null, effective_date: string, expiry_date: string, premium_amount: string, discount_amount?: string|null, status: string, source: string}  $attributes
     *
     * @throws \Throwable
     */
    public function handle(User $user, array $attributes): Policy
    {
        return DB::transaction(function () use ($user, $attributes): Policy {
            $organizationId = $this->organizationContext->id();

            $policyNumber = ! empty($attributes['policy_number'])
                ? $attributes['policy_number']
                : $this->generatePolicyNumber($organizationId);

            $slug = $this->generateUniqueSlug(Policy::class, $policyNumber, $organizationId);

            /** @var Policy $policy */
            $policy = Policy::query()->create([
                'organization_id' => $organizationId,
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
                'created_by' => $user->id,
            ]);

            return $policy;
        });
    }

    private function generatePolicyNumber(int $organizationId): string
    {
        $max = Policy::query()
            ->where('organization_id', $organizationId)
            ->where('policy_number', 'like', 'POL-%')
            ->pluck('policy_number')
            ->map(fn (string $policyNumber): int => (int) str_replace('POL-', '', $policyNumber))
            ->max() ?? 0;

        return 'POL-'.str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }
}
