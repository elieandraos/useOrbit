<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Models\Policy;
use App\Models\PolicyExpatDetails;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreatePolicyExpatAction
{
    public function __construct(
        private readonly CreatePolicyAction $createPolicyAction,
    ) {}

    /**
     * @param  array{policy_number?: string|null, class: string, subclass: string, type: string, client_id: int, carrier_id: int, agent_id?: int|null, effective_date: string, expiry_date: string, premium_amount: string, discount_amount?: string|null, status: string, source: string, expat: array{coverage_zone: string, travel_scope: string|null, full_name: string, gender: string, nationality: string, date_of_birth: string, phone: string, country_id: int|null, visa_expiry_date: string|null}}  $attributes
     *
     * @throws \Throwable
     */
    public function handle(User $user, array $attributes): Policy
    {
        return DB::transaction(function () use ($user, $attributes): Policy {
            $policy = $this->createPolicyAction->handle($user, $attributes);

            $this->createExpatDetails($policy, $attributes);

            return $policy;
        });
    }

    /**
     * @param  array{expat: array{coverage_zone: string, travel_scope: string|null, full_name: string, gender: string, nationality: string, date_of_birth: string, phone: string, country_id: int|null, visa_expiry_date: string|null}}  $attributes
     */
    private function createExpatDetails(Policy $policy, array $attributes): void
    {
        $expat = $attributes['expat'];

        PolicyExpatDetails::query()->create([
            'policy_id' => $policy->id,
            'coverage_zone' => $expat['coverage_zone'],
            'travel_scope' => $expat['travel_scope'] ?? null,
            'full_name' => $expat['full_name'],
            'gender' => $expat['gender'],
            'nationality' => $expat['nationality'],
            'date_of_birth' => $expat['date_of_birth'],
            'phone' => $expat['phone'],
            'country_id' => $expat['country_id'] ?? null,
            'visa_expiry_date' => $expat['visa_expiry_date'] ?? null,
        ]);
    }
}
