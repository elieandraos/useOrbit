<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CarrierStatus;
use App\Models\Carrier;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Carrier>
 */
class CarrierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'organization_id' => Organization::factory(),
            'slug' => Str::slug($name.'-'.fake()->unique()->numerify()),
            'name' => $name,
            'phone' => fake()->phoneNumber(),
            'website' => Str::slug($name, '').'.com',
            'onboarded_date' => fake()->dateTimeBetween('-3 years')->format('Y-m-d'),
            'status' => CarrierStatus::Active->value,
            'created_by' => User::factory(),
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CarrierStatus::Archived->value,
        ]);
    }

    public function forOrganization(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'organization_id' => $user->current_organization_id,
        ]);
    }
}
