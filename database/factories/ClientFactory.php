<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ClientStatus;
use App\Enums\Gender;
use App\Enums\LeadSource;
use App\Models\Client;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Nnjeim\World\Models\City;
use Nnjeim\World\Models\Country;
use Nnjeim\World\Models\State;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gender = fake()->randomElement(Gender::cases());
        $firstName = fake()->firstName($gender->value);
        $middleName = fake()->firstName(Gender::Male->value);
        $lastName = fake()->lastName();
        $mothersName = fake()->firstName(Gender::Female->value);
        $emailDomain = fake()->randomElement(['gmail.com', 'outlook.com', 'yahoo.com', 'hotmail.com', 'icloud.com']);

        $country = Country::query()->firstOrCreate(
            ['iso2' => 'LB'],
            ['name' => 'Lebanon', 'iso3' => 'LBN', 'phone_code' => '961', 'region' => 'Asia', 'subregion' => 'Western Asia'],
        );
        $state = State::query()->firstOrCreate(
            ['name' => 'Mount Lebanon', 'country_id' => $country->id],
        );
        $city = City::query()->firstOrCreate(
            ['name' => 'Jounieh', 'state_id' => $state->id, 'country_id' => $country->id],
            ['country_code' => 'LB'],
        );

        return [
            'organization_id' => Organization::factory(),
            'slug' => Str::slug($firstName.'-'.$lastName.'-'.fake()->unique()->numerify()),
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'mothers_name' => $mothersName,
            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'gender' => $gender->value,
            'phone' => fake()->phoneNumber(),
            'email' => Str::slug($firstName, '_').'_'.Str::slug($lastName, '_').'@'.$emailDomain,
            'street' => fake()->buildingNumber().' '.fake()->streetName(),
            'building_floor' => fake()->randomElement(['Ground Floor', '1st Floor', '2nd Floor', '3rd Floor', '4th Floor', '5th Floor']),
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'enrollment_date' => fake()->dateTimeBetween('-2 years')->format('Y-m-d'),
            'lead_source' => fake()->randomElement(LeadSource::cases())->value,
            'status' => ClientStatus::Active->value,
            'created_by' => User::factory(),
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ClientStatus::Archived->value,
        ]);
    }
}
