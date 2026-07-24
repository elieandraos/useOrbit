<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use App\Models\Note;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
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
            'notable_type' => (new Client)->getMorphClass(),
            'notable_id' => Client::factory(),
            'created_by' => User::factory(),
            'body' => fake()->paragraph(),
            'pinned' => false,
        ];
    }

    public function pinned(): static
    {
        return $this->state(fn (array $attributes): array => [
            'pinned' => true,
        ]);
    }

    public function forOrganization(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'organization_id' => $user->current_organization_id,
        ]);
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'created_by' => $user->id,
        ]);
    }
}
