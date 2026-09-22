<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Models\Policy;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdatePolicyLifeAction
{
    public function __construct(
        private readonly UpdatePolicyAction $updatePolicyAction,
    ) {}

    /**
     * @param  array{policy_number?: string|null, class: string, subclass: string, type: string, client_id: int, carrier_id: int, agent_id?: int|null, effective_date: string, expiry_date: string, premium_amount: string, discount_amount?: string|null, status: string, source: string, life: array{sum_assured: string, term_years: int, smoker: bool, beneficiaries: string}}  $attributes
     *
     * @throws \Throwable
     */
    public function handle(User $user, Policy $policy, array $attributes): Policy
    {
        return DB::transaction(function () use ($user, $policy, $attributes): Policy {
            $policy = $this->updatePolicyAction->handle($user, $policy, $attributes);

            $this->updateLifeDetails($policy, $attributes);

            return $policy->fresh();
        });
    }

    /**
     * @param  array{life: array{sum_assured: string, term_years: int, smoker: bool, beneficiaries: string}}  $attributes
     */
    private function updateLifeDetails(Policy $policy, array $attributes): void
    {
        $life = $attributes['life'];

        $policy->lifeDetails->update([
            'sum_assured' => $life['sum_assured'],
            'term_years' => $life['term_years'],
            'smoker' => $life['smoker'],
            'beneficiaries' => $life['beneficiaries'],
        ]);
    }
}
