<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Models\Policy;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdatePolicyExpatAction
{
    public function __construct(
        private readonly UpdatePolicyAction $updatePolicyAction,
    ) {}

    /**
     * @param  array{policy_number: string, class: string, subclass: string, type: string, client_id: string, carrier_id: string, carrier_branch_id?: string|null, agent_id?: string|null, currency_id: string, effective_date: string, expiry_date: string, premium_amount: string, discount_amount?: string|null, source: string, expat: array{coverage_zone: string, travel_scope: string|null, full_name: string, gender: string, nationality: string, date_of_birth: string, phone: string, country_id: string|null, visa_expiry_date: string|null}}  $attributes
     *
     * @throws \Throwable
     */
    public function handle(User $user, Policy $policy, array $attributes): Policy
    {
        return DB::transaction(function () use ($user, $policy, $attributes): Policy {
            $policy = $this->updatePolicyAction->handle($user, $policy, $attributes);

            $this->updateExpatDetails($policy, $attributes['expat']);

            return $policy->fresh();
        });
    }

    /**
     * @param  array{coverage_zone: string, travel_scope: string|null, full_name: string, gender: string, nationality: string, date_of_birth: string, phone: string, country_id: string|null, visa_expiry_date: string|null}  $expat
     */
    private function updateExpatDetails(Policy $policy, array $expat): void
    {
        $policy->expatDetails->update([
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
