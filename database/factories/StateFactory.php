<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Country;
use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<State>
 */
class StateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'country_id' => Country::factory(),
            'name' => fake()->unique()->city(),
        ];
    }

    /**
     * A state in Lebanon, the operating market the test environment configures.
     */
    public function lebanon(): static
    {
        return $this->state(fn (): array => [
            'country_id' => Country::query()->firstOrCreate(
                ['iso2' => 'LB'],
                ['name' => 'Lebanon', 'iso3' => 'LBN', 'phone_code' => '961', 'region' => 'Asia', 'subregion' => 'Western Asia'],
            )->id,
        ]);
    }
}
