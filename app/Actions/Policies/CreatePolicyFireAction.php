<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Models\Policy;
use App\Models\PolicyFireDetails;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreatePolicyFireAction
{
    public function __construct(
        private readonly CreatePolicyAction $createPolicyAction,
    ) {}

    /**
     * @param  array{policy_number?: string|null, class: string, subclass: string, type: string, client_id: int, carrier_id: int, agent_id?: int|null, effective_date: string, expiry_date: string, premium_amount: string, discount_amount?: string|null, status: string, source: string, fire: array{property_type: string, floor_area: int, year_built: int|null, street: string, building_floor: string|null, city: string, state_id: int, country_id: int, sum_insured: string}}  $attributes
     *
     * @throws \Throwable
     */
    public function handle(User $user, array $attributes): Policy
    {
        return DB::transaction(function () use ($user, $attributes): Policy {
            $policy = $this->createPolicyAction->handle($user, $attributes);

            $this->createFireDetails($policy, $attributes);

            return $policy;
        });
    }

    /**
     * @param  array{fire: array{property_type: string, floor_area: int, year_built: int|null, street: string, building_floor: string|null, city: string, state_id: int, country_id: int, sum_insured: string}}  $attributes
     */
    private function createFireDetails(Policy $policy, array $attributes): void
    {
        $fire = $attributes['fire'];

        PolicyFireDetails::query()->create([
            'policy_id' => $policy->id,
            'property_type' => $fire['property_type'],
            'floor_area' => $fire['floor_area'],
            'year_built' => $fire['year_built'] ?? null,
            'street' => $fire['street'],
            'building_floor' => $fire['building_floor'] ?? null,
            'city' => $fire['city'],
            'state_id' => $fire['state_id'],
            'country_id' => $fire['country_id'],
            'sum_insured' => $fire['sum_insured'],
        ]);
    }
}
