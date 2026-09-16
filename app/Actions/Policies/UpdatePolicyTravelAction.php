<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Models\Policy;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdatePolicyTravelAction
{
    public function __construct(
        private readonly UpdatePolicyAction $updatePolicyAction,
    ) {}

    /**
     * @param  array{policy_number?: string|null, class: string, subclass: string, type: string, client_id: int, carrier_id: int, agent_id?: int|null, effective_date: string, expiry_date: string, premium_amount: string, discount_amount?: string|null, status: string, source: string, travel: array{destination: string, trip_start_date: string, trip_end_date: string, travelers: string, coverage_tier: string}}  $attributes
     *
     * @throws \Throwable
     */
    public function handle(User $user, Policy $policy, array $attributes): Policy
    {
        return DB::transaction(function () use ($user, $policy, $attributes): Policy {
            $policy = $this->updatePolicyAction->handle($user, $policy, $attributes);

            $this->updateTravelDetails($policy, $attributes);

            return $policy->fresh();
        });
    }

    /**
     * @param  array{travel: array{destination: string, trip_start_date: string, trip_end_date: string, travelers: string, coverage_tier: string}}  $attributes
     */
    private function updateTravelDetails(Policy $policy, array $attributes): void
    {
        $travel = $attributes['travel'];

        $policy->travelDetails->update([
            'destination' => $travel['destination'],
            'trip_start_date' => $travel['trip_start_date'],
            'trip_end_date' => $travel['trip_end_date'],
            'travelers' => $travel['travelers'],
            'coverage_tier' => $travel['coverage_tier'],
        ]);
    }
}
