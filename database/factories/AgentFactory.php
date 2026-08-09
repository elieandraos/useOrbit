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
        $dateOfBirth = fake()->dateTimeBetween('-60 years', '-21 years');
        $joinedAt = fake()->dateTimeBetween((clone $dateOfBirth)->modify('+21 years'), 'now');

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
            'first_name' => $firstName,
            'last_name' => $lastName,
            'date_of_birth' => $dateOfBirth->format('Y-m-d'),
            'joined_at' => $joinedAt->format('Y-m-d'),
            'phone' => fake()->phoneNumber(),
            'email' => null,
            'street' => fake()->buildingNumber().' '.fake()->streetName(),
            'building_floor' => fake()->randomElement(['Ground Floor', '1st Floor', '2nd Floor', '3rd Floor', '4th Floor', '5th Floor']),
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city' => 'Jounieh',
            'status' => AgentStatus::Active->value,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Derive slug/email from the final first_name/last_name (after any factory
     * state or ->create([...]) override), not the random name computed in definition().
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Agent $agent): void {
            $agent->slug ??= Str::slug($agent->first_name.'-'.$agent->last_name.'-'.fake()->unique()->numerify());
            $agent->email ??= Str::slug($agent->first_name, '_').'_'.Str::slug($agent->last_name, '_').'@'.fake()->randomElement(['gmail.com', 'outlook.com', 'yahoo.com', 'hotmail.com', 'icloud.com']);
        });
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
            'organization_id' => $user->organization_id,
        ]);
    }
}
