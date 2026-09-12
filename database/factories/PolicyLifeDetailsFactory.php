<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PolicyClass;
use App\Models\Policy;
use App\Models\PolicyLifeDetails;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PolicyLifeDetails>
 */
class PolicyLifeDetailsFactory extends Factory
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
                'class' => PolicyClass::Life->value,
                'subclass' => 'Standard',
            ]),
            'sum_assured' => fake()->randomFloat(2, 20000, 500000),
            'term_years' => fake()->numberBetween(5, 30),
            'smoker' => fake()->boolean(),
            'beneficiaries' => fake()->name().' (100%)',
        ];
    }
}
