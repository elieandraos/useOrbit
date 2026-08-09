<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Models\Client;
use App\Models\Document;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $extension = fake()->randomElement(['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx']);
        $filename = Str::slug(fake()->words(3, true)).'.'.$extension;

        return [
            'organization_id' => Organization::factory(),
            'documentable_type' => (new Client)->getMorphClass(),
            'documentable_id' => Client::factory(),
            'uploaded_by' => User::factory(),
            'original_filename' => $filename,
            'disk' => 'local',
            'path' => 'documents-staging/'.fake()->uuid(),
            'mime_type' => fake()->mimeType(),
            'size_in_bytes' => fake()->numberBetween(1024, 25 * 1024 * 1024),
            'checksum' => fake()->sha256(),
            'status' => DocumentStatus::Pending,
            'stored_at' => null,
            'error_message' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'path' => 'documents/'.fake()->uuid(),
            'status' => DocumentStatus::Completed,
            'stored_at' => now(),
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => DocumentStatus::Processing,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => DocumentStatus::Failed,
            'error_message' => 'Unable to store the file.',
        ]);
    }

    public function forOrganization(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'organization_id' => $user->organization_id,
        ]);
    }

    public function uploadedBy(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'uploaded_by' => $user->id,
        ]);
    }
}
