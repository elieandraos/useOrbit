<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Country;
use App\Models\Currency;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
        ];
    }

    /**
     * An organization defaulting to Lebanon and the US Dollar, the demo organization's defaults.
     */
    public function withLebanonAndUsdDefaults(): static
    {
        return $this->state(fn (): array => [
            'default_country_id' => Country::query()->firstOrCreate(
                ['iso2' => 'LB'],
                ['name' => 'Lebanon', 'iso3' => 'LBN', 'phone_code' => '961', 'region' => 'Asia', 'subregion' => 'Western Asia'],
            )->id,
            'default_currency_id' => Currency::query()->firstOrCreate(
                ['code' => 'USD'],
                ['name' => 'US Dollar'],
            )->id,
        ]);
    }
}
