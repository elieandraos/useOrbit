<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Carrier;
use App\Models\CarrierBranch;
use App\Models\Country;
use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CarrierBranch>
 */
class CarrierBranchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $contactName = fake()->name();
        $emailDomain = fake()->randomElement(['gmail.com', 'outlook.com', 'yahoo.com', 'hotmail.com', 'icloud.com']);

        $country = Country::query()->firstOrCreate(
            ['iso2' => 'LB'],
            ['name' => 'Lebanon', 'iso3' => 'LBN', 'phone_code' => '961', 'region' => 'Asia', 'subregion' => 'Western Asia'],
        );
        $state = State::query()->firstOrCreate(
            ['name' => 'Mount Lebanon', 'country_id' => $country->id],
        );

        return [
            'carrier_id' => Carrier::factory(),
            'is_hq' => true,
            'street' => fake()->buildingNumber().' '.fake()->streetName(),
            'building_floor' => fake()->randomElement(['Ground Floor', '1st Floor', '2nd Floor', '3rd Floor']),
            'city' => 'Beirut',
            'state_id' => $state->id,
            'country_id' => $country->id,
            'phone' => fake()->phoneNumber(),
            'contact_name' => $contactName,
            'contact_role' => fake()->jobTitle(),
            'contact_email' => str(preg_replace('/[^a-z\s]/', '', mb_strtolower($contactName)))->slug('.').'@'.$emailDomain,
            'contact_phone' => fake()->phoneNumber(),
            'contact_department' => fake()->randomElement(['Operations', 'Underwriting', 'Broker Relations', 'Claims']),
        ];
    }

    public function forCarrier(Carrier $carrier): static
    {
        return $this->state(fn (array $attributes): array => [
            'carrier_id' => $carrier->id,
        ]);
    }
}
