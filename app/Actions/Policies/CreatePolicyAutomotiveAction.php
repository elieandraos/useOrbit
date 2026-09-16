<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Models\Policy;
use App\Models\PolicyAutomotiveDetails;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreatePolicyAutomotiveAction
{
    public function __construct(
        private readonly CreatePolicyAction $createPolicyAction,
    ) {}

    /**
     * @param  array{policy_number?: string|null, class: string, subclass: string, type: string, client_id: int, carrier_id: int, agent_id?: int|null, effective_date: string, expiry_date: string, premium_amount: string, discount_amount?: string|null, status: string, source: string, automotive: array{plate_number: string, make: string, model: string, year: int, vin: string|null, color: string|null, valuation_amount: string|null, valuation_source: string|null}}  $attributes
     *
     * @throws \Throwable
     */
    public function handle(User $user, array $attributes): Policy
    {
        return DB::transaction(function () use ($user, $attributes): Policy {
            $policy = $this->createPolicyAction->handle($user, $attributes);

            $this->createAutomotiveDetails($policy, $attributes);

            return $policy;
        });
    }

    /**
     * @param  array{automotive: array{plate_number: string, make: string, model: string, year: int, vin: string|null, color: string|null, valuation_amount: string|null, valuation_source: string|null}}  $attributes
     */
    private function createAutomotiveDetails(Policy $policy, array $attributes): void
    {
        $automotive = $attributes['automotive'];

        PolicyAutomotiveDetails::query()->create([
            'policy_id' => $policy->id,
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
