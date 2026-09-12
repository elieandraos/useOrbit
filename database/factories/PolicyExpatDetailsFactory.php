<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ExpatCoverageZone;
use App\Enums\Gender;
use App\Enums\PolicyClass;
use App\Models\Country;
use App\Models\Policy;
use App\Models\PolicyExpatDetails;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PolicyExpatDetails>
 */
class PolicyExpatDetailsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $coverageZone = fake()->randomElement(ExpatCoverageZone::cases());
        $gender = fake()->randomElement(Gender::cases());

        $country = Country::query()->firstOrCreate(
            ['iso2' => 'LB'],
            ['name' => 'Lebanon', 'iso3' => 'LBN', 'phone_code' => '961', 'region' => 'Asia', 'subregion' => 'Western Asia'],
        );

        return [
            'policy_id' => Policy::factory()->state(fn (): array => [
                'class' => PolicyClass::Expat->value,
                'subclass' => $coverageZone === ExpatCoverageZone::In ? 'In' : 'In-Out',
            ]),
            'coverage_zone' => $coverageZone->value,
            'travel_scope' => $coverageZone === ExpatCoverageZone::InOut
                ? fake()->randomElement(['Worldwide', 'Worldwide ex-USA/Canada', 'Regional'])
                : null,
            'full_name' => fake()->name($gender->value),
            'gender' => $gender->value,
            'nationality' => fake()->country(),
            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'phone' => fake()->phoneNumber(),
            'country_id' => $country->id,
            'visa_expiry_date' => fake()->optional()->dateTimeBetween('now', '+2 years')?->format('Y-m-d'),
        ];
    }
}
