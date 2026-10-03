<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Models\Policy;
use App\Models\PolicyTravelDetails;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreatePolicyTravelAction
{
    public function __construct(
        private readonly CreatePolicyAction $createPolicyAction,
    ) {}

    /**
     * @param  array{policy_number: string, class: string, subclass: string, type: string, client_id: string, carrier_id: string, carrier_branch_id?: string|null, agent_id?: string|null, currency_id: string, effective_date: string, expiry_date: string, premium_amount: string, discount_amount?: string|null, status: string, source: string, travel: array{destination: string, trip_start_date: string, trip_end_date: string, travelers: string, coverage_tier: string}}  $attributes
     *
     * @throws \Throwable
     */
    public function handle(User $user, array $attributes): Policy
    {
        return DB::transaction(function () use ($user, $attributes): Policy {
            $policy = $this->createPolicyAction->handle($user, $attributes);

            $this->createTravelDetails($policy, $attributes['travel']);

            return $policy;
        });
    }

    /**
     * @param  array{destination: string, trip_start_date: string, trip_end_date: string, travelers: string, coverage_tier: string}  $travel
     */
    private function createTravelDetails(Policy $policy, array $travel): void
    {
        PolicyTravelDetails::query()->create([
            'policy_id' => $policy->id,
            'destination' => $travel['destination'],
            'trip_start_date' => $travel['trip_start_date'],
            'trip_end_date' => $travel['trip_end_date'],
            'travelers' => $travel['travelers'],
            'coverage_tier' => $travel['coverage_tier'],
        ]);
    }
}
