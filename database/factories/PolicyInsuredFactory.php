<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Gender;
use App\Enums\PolicyClass;
use App\Models\Policy;
use App\Models\PolicyInsured;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PolicyInsured>
 */
class PolicyInsuredFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gender = fake()->randomElement(Gender::cases());

        return [
            'policy_id' => Policy::factory()->state(fn (): array => [
                'class' => PolicyClass::Medical->value,
                'subclass' => 'In',
            ]),
            'member_code' => 'MBR-'.fake()->unique()->numerify('###'),
            'full_name' => fake()->name($gender->value),
            'relationship' => fake()->randomElement(['Employee', 'Spouse', 'Child', 'Parent']),
            'date_of_birth' => fake()->dateTimeBetween('-65 years', '-1 year')->format('Y-m-d'),
            'gender' => $gender->value,
            'medical_notes' => fake()->optional()->sentence(),
            'status' => fake()->randomElement(['Active', 'Pending']),
        ];
    }
}
