<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ClientStatus;
use App\Enums\ClientType;
use App\Enums\Gender;
use App\Enums\LeadSource;
use App\Models\Client;
use App\Models\Country;
use App\Models\Organization;
use App\Models\State;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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

        $country = Country::query()->firstOrCreate(
            ['iso2' => 'LB'],
            ['name' => 'Lebanon', 'iso3' => 'LBN', 'phone_code' => '961', 'region' => 'Asia', 'subregion' => 'Western Asia'],
        );
        $state = State::query()->firstOrCreate(
            ['name' => 'Mount Lebanon', 'country_id' => $country->id],
        );

        return [
            'organization_id' => Organization::factory(),
            'slug' => null,
            'client_type' => ClientType::Individual->value,
            'company_name' => null,
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'mothers_name' => $mothersName,
            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'gender' => $gender->value,
            'phone' => fake()->phoneNumber(),
            'email' => null,
            'street' => fake()->buildingNumber().' '.fake()->streetName(),
            'building_floor' => fake()->randomElement(['Ground Floor', '1st Floor', '2nd Floor', '3rd Floor', '4th Floor', '5th Floor']),
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city' => 'Jounieh',
            'enrollment_date' => fake()->dateTimeBetween('-2 years')->format('Y-m-d'),
            'lead_source' => fake()->randomElement(LeadSource::cases())->value,
            'status' => ClientStatus::Active->value,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Derive slug/email from the final first_name/last_name (after any factory
     * state or ->create([...]) override), not the random name computed in definition().
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Client $client): void {
            $client->slug ??= Str::slug($client->first_name.'-'.$client->last_name.'-'.fake()->unique()->numerify());
            $client->email ??= Str::slug($client->first_name, '_').'_'.Str::slug($client->last_name, '_').'@'.fake()->randomElement(['gmail.com', 'outlook.com', 'yahoo.com', 'hotmail.com', 'icloud.com']);
        });
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ClientStatus::Archived->value,
        ]);
    }

    public function company(): static
    {
        return $this->state(fn (array $attributes): array => [
            'client_type' => ClientType::Company->value,
            'company_name' => fake()->company(),
            'date_of_birth' => null,
            'gender' => null,
            'mothers_name' => null,
        ]);
    }

    public function forOrganization(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'organization_id' => $user->organization_id,
        ]);
    }
}
