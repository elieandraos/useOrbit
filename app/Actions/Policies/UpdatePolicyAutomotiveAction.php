<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Models\Policy;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdatePolicyAutomotiveAction
{
    public function __construct(
        private readonly UpdatePolicyAction $updatePolicyAction,
    ) {}

    /**
     * @param  array{policy_number?: string|null, class: string, subclass: string, type: string, client_id: int, carrier_id: int, agent_id?: int|null, effective_date: string, expiry_date: string, premium_amount: string, discount_amount?: string|null, status: string, source: string, automotive: array{plate_number: string, make: string, model: string, year: int, vin: string|null, color: string|null, valuation_amount: string|null, valuation_source: string|null}}  $attributes
     *
     * @throws \Throwable
     */
    public function handle(User $user, Policy $policy, array $attributes): Policy
    {
        return DB::transaction(function () use ($user, $policy, $attributes): Policy {
            $policy = $this->updatePolicyAction->handle($user, $policy, $attributes);

            $this->updateAutomotiveDetails($policy, $attributes);

            return $policy->fresh();
        });
    }

    /**
     * @param  array{automotive: array{plate_number: string, make: string, model: string, year: int, vin: string|null, color: string|null, valuation_amount: string|null, valuation_source: string|null}}  $attributes
     */
    private function updateAutomotiveDetails(Policy $policy, array $attributes): void
    {
        $automotive = $attributes['automotive'];

        $policy->automotiveDetails->update([
            'plate_number' => $automotive['plate_number'],
            'make' => $automotive['make'],
            'model' => $automotive['model'],
            'year' => $automotive['year'],
            'vin' => $automotive['vin'] ?? null,
            'color' => $automotive['color'] ?? null,
            'valuation_amount' => $automotive['valuation_amount'] ?? null,
            'valuation_source' => $automotive['valuation_source'] ?? null,
        ]);
    }
}
