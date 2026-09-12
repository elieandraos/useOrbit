<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PolicyClass;
use App\Models\Policy;
use App\Models\PolicyTravelDetails;
use Illuminate\Database\Eloquent\Factories\Factory;

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
        $tripStartDate = fake()->dateTimeBetween('+1 week', '+3 months');
        $tripEndDate = (clone $tripStartDate)->modify('+'.fake()->numberBetween(3, 21).' days');

        return [
            'policy_id' => Policy::factory()->state(fn (): array => [
                'class' => PolicyClass::Travel->value,
                'subclass' => 'Standard',
            ]),
            'destination' => fake()->country(),
            'trip_start_date' => $tripStartDate->format('Y-m-d'),
            'trip_end_date' => $tripEndDate->format('Y-m-d'),
            'travelers' => fake()->name(),
            'coverage_tier' => fake()->randomElement(['Basic', 'Standard', 'Premium']),
        ];
    }
}
