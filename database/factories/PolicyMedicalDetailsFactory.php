<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Gender;
use App\Enums\MedicalClassTier;
use App\Enums\MedicalCoverageScope;
use App\Enums\PolicyClass;
use App\Models\Policy;
use App\Models\PolicyMedicalDetails;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PolicyMedicalDetails>
 */
class PolicyMedicalDetailsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $coInsurance = fake()->boolean();
        $gender = fake()->randomElement(Gender::cases());

        return [
            'policy_id' => Policy::factory()->state(fn (): array => [
                'class' => PolicyClass::Medical->value,
                'subclass' => fake()->randomElement(['In', 'In-Out']),
            ]),
            'coverage_scope' => fake()->randomElement(MedicalCoverageScope::cases())->value,
            'class_tier' => fake()->randomElement(MedicalClassTier::cases())->value,
            'co_insurance' => $coInsurance,
            'co_insurance_share' => $coInsurance ? fake()->randomFloat(2, 10, 50) : null,
            'guaranteed_renewable' => fake()->boolean(),
            'insured_full_name' => fake()->name($gender->value),
            'insured_date_of_birth' => fake()->dateTimeBetween('-70 years', '-18 years')->format('Y-m-d'),
            'insured_gender' => $gender->value,
            'insured_smoker' => fake()->boolean(),
            'insured_medical_history' => fake()->optional()->sentence(),
        ];
    }
}
