<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Lebanon's codes are never generated, because the address factories create Lebanon itself with `firstOrCreate`.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        do {
            $iso2 = fake()->unique()->countryCode();
        } while ($iso2 === 'LB');

        do {
            $iso3 = fake()->unique()->countryISOAlpha3();
        } while ($iso3 === 'LBN');

        return [
            'name' => fake()->country(),
            'iso2' => $iso2,
            'iso3' => $iso3,
            'phone_code' => (string) fake()->numberBetween(1, 999),
            'region' => fake()->randomElement(['Africa', 'Americas', 'Asia', 'Europe', 'Oceania']),
            'subregion' => null,
        ];
    }
}
