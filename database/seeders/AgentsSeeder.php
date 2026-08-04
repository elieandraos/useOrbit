<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Organization;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

final class AgentsSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organization = Organization::query()->firstOrFail();
        $user = $organization->owner();

        Agent::factory()->count(8)->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);
    }
}
