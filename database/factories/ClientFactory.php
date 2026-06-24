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
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'organization_id' => Organization::factory(),
            'slug' => Str::slug($firstName.'-'.$lastName.'-'.fake()->unique()->numerify()),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(Gender::cases())->value,
            'phone' => fake()->phoneNumber(),
            'enrollment_date' => fake()->dateTimeBetween('-2 years')->format('Y-m-d'),
            'lead_source' => fake()->randomElement(LeadSource::cases())->value,
            'status' => ClientStatus::Active->value,
            'created_by' => User::factory(),
        ];
    }
}
