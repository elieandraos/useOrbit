<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Carrier;
use App\Models\CarrierBranch;
use App\Models\Organization;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

final class CarriersSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organization = Organization::query()->firstOrFail();
        $user = $organization->owner();

        Carrier::factory()
            ->count(5)
            ->create([
                'organization_id' => $organization->id,
                'created_by' => $user->id,
            ])
            ->each(fn (Carrier $carrier) => CarrierBranch::factory()->forCarrier($carrier)->create());
    }
}
