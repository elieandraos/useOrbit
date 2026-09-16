<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Models\Policy;
use App\Models\PolicyLifeDetails;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreatePolicyLifeAction
{
    public function __construct(
        private readonly CreatePolicyAction $createPolicyAction,
    ) {}

    /**
     * @param  array{policy_number?: string|null, class: string, subclass: string, type: string, client_id: int, carrier_id: int, agent_id?: int|null, effective_date: string, expiry_date: string, premium_amount: string, discount_amount?: string|null, status: string, source: string, life: array{sum_assured: string, term_years: int, smoker: bool, beneficiaries: string}}  $attributes
     *
     * @throws \Throwable
     */
    public function handle(User $user, array $attributes): Policy
    {
        return DB::transaction(function () use ($user, $attributes): Policy {
            $policy = $this->createPolicyAction->handle($user, $attributes);

            $this->createLifeDetails($policy, $attributes);

            return $policy;
        });
    }

    /**
     * @param  array{life: array{sum_assured: string, term_years: int, smoker: bool, beneficiaries: string}}  $attributes
     */
    private function createLifeDetails(Policy $policy, array $attributes): void
    {
        $life = $attributes['life'];

        PolicyLifeDetails::query()->create([
            'policy_id' => $policy->id,
            'sum_assured' => $life['sum_assured'],
            'term_years' => $life['term_years'],
            'smoker' => $life['smoker'],
            'beneficiaries' => $life['beneficiaries'],
        ]);
    }
}
