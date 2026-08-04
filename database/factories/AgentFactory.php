<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AgentStatus;
use App\Models\Agent;
use App\Models\Country;
use App\Models\Organization;
use App\Models\State;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Agent>
 */
class AgentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();
        $emailDomain = fake()->randomElement(['gmail.com', 'outlook.com', 'yahoo.com', 'hotmail.com', 'icloud.com']);

        $country = Country::query()->firstOrCreate(
            ['iso2' => 'LB'],
            ['name' => 'Lebanon', 'iso3' => 'LBN', 'phone_code' => '961', 'region' => 'Asia', 'subregion' => 'Western Asia'],
        );
        $state = State::query()->firstOrCreate(
            ['name' => 'Mount Lebanon', 'country_id' => $country->id],
        );

        return [
            'organization_id' => Organization::factory(),
            'slug' => Str::slug($firstName.'-'.$lastName.'-'.fake()->unique()->numerify()),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-21 years')->format('Y-m-d'),
            'phone' => fake()->phoneNumber(),
            'email' => Str::slug($firstName, '_').'_'.Str::slug($lastName, '_').'@'.$emailDomain,
            'street' => fake()->buildingNumber().' '.fake()->streetName(),
            'building_floor' => fake()->randomElement(['Ground Floor', '1st Floor', '2nd Floor', '3rd Floor', '4th Floor', '5th Floor']),
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city' => 'Jounieh',
            'status' => AgentStatus::Active->value,
            'created_by' => User::factory(),
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AgentStatus::Archived->value,
        ]);
    }

    public function forOrganization(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'organization_id' => $user->current_organization_id,
        ]);
    }
}
