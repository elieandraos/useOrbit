<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PolicyClass;
use App\Models\Policy;
use App\Models\PolicyAutomotiveDetails;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PolicyAutomotiveDetails>
 */
class PolicyAutomotiveDetailsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subclass = fake()->randomElement(['Third Party Liability', 'All Risk']);
        $isAllRisk = $subclass === 'All Risk';

        return [
            'policy_id' => Policy::factory()->state(fn (): array => [
                'class' => PolicyClass::Automotive->value,
                'subclass' => $subclass,
            ]),
            'plate_number' => strtoupper(fake()->bothify('### ??')),
            'make' => fake()->randomElement(['BMW', 'Mercedes-Benz', 'Toyota', 'Audi', 'Honda']),
            'model' => fake()->word(),
            'year' => fake()->numberBetween(2010, (int) date('Y')),
            'vin' => fake()->optional()->regexify('[A-HJ-NPR-Z0-9]{17}'),
            'color' => fake()->optional()->safeColorName(),
            'valuation_amount' => $isAllRisk ? fake()->randomFloat(2, 5000, 150000) : null,
            'valuation_source' => $isAllRisk ? fake()->randomElement(['Carrier assessor', 'Independent appraisal', 'Market value']) : null,
        ];
    }
}
