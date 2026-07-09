<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Organization;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

final class ClientsSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organization = Organization::query()->firstOrFail();
        $user = $organization->owner();

        Client::factory()->count(25)->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);
    }
}
