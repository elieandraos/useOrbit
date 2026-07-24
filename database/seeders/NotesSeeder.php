<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Note;
use App\Models\Organization;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

final class NotesSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organization = Organization::query()->firstOrFail();
        $user = $organization->owner();

        Client::query()->each(function (Client $client) use ($user): void {
            $count = fake()->numberBetween(0, 3);

            if ($count === 0) {
                return;
            }

            $alreadyPinned = $client->notes()->where('pinned', true)->exists();
            $pinnedIndex = ! $alreadyPinned && fake()->boolean(45) ? fake()->numberBetween(0, $count - 1) : null;
            $createdAt = now()->subDays(fake()->numberBetween(30, 180));

            for ($i = 0; $i < $count; $i++) {
                Note::factory()->for($client, 'notable')->forOrganization($user)->createdBy($user)->create([
                    'pinned' => $i === $pinnedIndex,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                $createdAt = $createdAt->addDays(fake()->numberBetween(3, 45))->min(now());
            }
        });
    }
}
