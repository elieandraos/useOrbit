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
     * @param  array{policy_number: string, class: string, subclass: string, type: string, client_id: string, carrier_id: string, carrier_branch_id?: string|null, agent_id?: string|null, currency_id: string, effective_date: string, expiry_date: string, premium_amount: string, discount_amount?: string|null, status: string, source: string, life: array{sum_assured: string, term_years: string, smoker: bool, beneficiaries: string}}  $attributes
     *
     * @throws \Throwable
     */
    public function handle(User $user, array $attributes): Policy
    {
        return DB::transaction(function () use ($user, $attributes): Policy {
            $policy = $this->createPolicyAction->handle($user, $attributes);

            $this->createLifeDetails($policy, $attributes['life']);

            return $policy;
        });
    }

    /**
     * @param  array{sum_assured: string, term_years: string, smoker: bool, beneficiaries: string}  $life
     */
    private function createLifeDetails(Policy $policy, array $life): void
    {
        PolicyLifeDetails::query()->create([
            'policy_id' => $policy->id,
            'sum_assured' => $life['sum_assured'],
            'term_years' => $life['term_years'],
            'smoker' => $life['smoker'],
            'beneficiaries' => $life['beneficiaries'],
        ]);
    }
}
