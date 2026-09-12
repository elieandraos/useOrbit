<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PolicyType;
use App\Models\Agent;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\PolicyInsured;
use App\Support\Tenancy\OrganizationContext;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class PoliciesSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organization = Organization::query()->firstOrFail();
        $user = $organization->owner();

        app(OrganizationContext::class)->set($organization->id);

        $clients = Client::query()->get();
        $carriers = Carrier::query()->get();
        $agents = Agent::query()->get();

        // PolicyFactory's default policy_number only avoids collisions within one PHP process
        // (fake()->unique()), so repeated `db:seed` runs against the same persistent database
        // can and do collide on the unique (organization_id, policy_number) index. Seeded data
        // needs a wider, effectively-unique value across separate runs.
        $attributes = fn (): array => [
            'client_id' => $clients->random()->id,
            'carrier_id' => $carriers->random()->id,
            'agent_id' => fake()->boolean() ? $agents->random()->id : null,
            'created_by' => $user->id,
            'type' => PolicyType::Single->value,
            'policy_number' => 'POL-'.Str::upper(Str::random(8)),
        ];

        foreach (['medical', 'automotive', 'expat', 'fire', 'life', 'travel'] as $state) {
            Policy::factory()->count(4)->forOrganization($user)->{$state}()->state($attributes)->create();
        }

        /** @var Policy $groupMedicalPolicy */
        $groupMedicalPolicy = Policy::factory()->forOrganization($user)->medical()->create([
            ...($attributes)(),
            'type' => PolicyType::Group->value,
        ]);

        PolicyInsured::factory()
            ->for($groupMedicalPolicy)
            ->count(4)
            ->sequence(
                ['relationship' => 'Employee'],
                ['relationship' => 'Spouse'],
                ['relationship' => 'Child'],
                ['relationship' => 'Child'],
            )
            ->create();
    }
}
