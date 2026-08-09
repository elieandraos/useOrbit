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
        return [
            'organization_id' => Organization::factory(),
            'slug' => null,
            'name' => fake()->company(),
            'phone' => fake()->phoneNumber(),
            'website' => null,
            'status' => CarrierStatus::Active->value,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Derive slug/website from the final name (after any factory state or
     * ->create([...]) override), not the random name computed in definition().
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Carrier $carrier): void {
            $carrier->slug ??= Str::slug($carrier->name.'-'.fake()->unique()->numerify());
            $carrier->website ??= Str::slug($carrier->name, '').'.com';
        });
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
            'organization_id' => $user->organization_id,
        ]);
    }
}
