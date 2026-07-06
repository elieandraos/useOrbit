<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

final class ClientsSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(Organization $organization, User $user): void
    {
        if (! app()->environment('local')) {
            return;
        }

        Client::factory()->count(25)->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);
    }
}
