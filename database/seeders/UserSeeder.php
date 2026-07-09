<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

final class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $organization = Organization::factory()->create(['name' => 'Demo Org']);

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
