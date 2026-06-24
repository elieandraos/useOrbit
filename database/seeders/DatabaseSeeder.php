<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(CountrySeeder::class);

        $organization = Organization::factory()->create(['name' => 'Test Company']);

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'current_organization_id' => $organization->id,
        ]);

        $user->organizations()->attach($organization->id, [
            'role' => OrganizationRole::Owner->value,
            'status' => OrganizationMemberStatus::Active->value,
            'joined_at' => now(),
        ]);
    }
}
