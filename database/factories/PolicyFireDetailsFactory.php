<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PolicyClass;
use App\Models\Country;
use App\Models\Policy;
use App\Models\PolicyFireDetails;
use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PolicyFireDetails>
 */
class PolicyFireDetailsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $country = Country::query()->firstOrCreate(
            ['iso2' => 'LB'],
            ['name' => 'Lebanon', 'iso3' => 'LBN', 'phone_code' => '961', 'region' => 'Asia', 'subregion' => 'Western Asia'],
        );

        $state = State::query()->firstOrCreate(
            ['country_id' => $country->id, 'name' => 'Beirut'],
        );

        return [
            'policy_id' => Policy::factory()->state(fn (): array => [
                'class' => PolicyClass::Fire->value,
                'subclass' => 'Standard',
            ]),
            'property_type' => fake()->randomElement(['Residential apartment', 'Commercial building', 'Warehouse', 'Industrial facility']),
            'floor_area' => fake()->numberBetween(60, 500),
            'year_built' => fake()->optional()->numberBetween(1970, (int) date('Y')),
            'street' => fake()->streetAddress(),
            'building_floor' => fake()->optional()->buildingNumber(),
            'city' => 'Beirut',
            'state_id' => $state->id,
            'country_id' => $country->id,
            'sum_insured' => fake()->randomFloat(2, 50000, 1000000),
        ];
    }
}
