<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PolicyClass;
use App\Enums\TravelCoverageTier;
use App\Models\Policy;
use App\Models\PolicyTravelDetails;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<PolicyTravelDetails>
 */
class PolicyTravelDetailsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'policy_id' => Policy::factory()->state(fn (): array => [
                'class' => PolicyClass::Travel->value,
            ]),
            'destination' => fake()->country(),
            'trip_start_date' => function (array $attributes): string {
                $policy = $this->coveringPolicy($attributes['policy_id']);

                return fake()->dateTimeBetween($policy->effective_date, $policy->expiry_date)->format('Y-m-d');
            },
            'trip_end_date' => function (array $attributes): string {
                $policy = $this->coveringPolicy($attributes['policy_id']);

                return Carbon::parse($attributes['trip_start_date'])
                    ->addDays(fake()->numberBetween(3, 21))
                    ->min($policy->expiry_date)
                    ->format('Y-m-d');
            },
            'travelers' => fake()->name(),
            'coverage_tier' => fake()->randomElement(TravelCoverageTier::cases())->value,
        ];
    }

    /**
     * The policy whose coverage period the generated trip must fall within.
     */
    private function coveringPolicy(int $policyId): Policy
    {
        /** @var Policy */
        return Policy::query()->withoutGlobalScopes()->findOrFail($policyId);
    }
}
