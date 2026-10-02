<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

final class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $country = Country::query()->firstOrCreate(
            ['iso2' => 'LB'],
            ['name' => 'Lebanon', 'iso3' => 'LBN', 'phone_code' => '961', 'region' => 'Asia', 'subregion' => 'Western Asia'],
        );

        $organization = Organization::factory()->create([
            'name' => 'Demo Org',
            'default_country_id' => $country->id,
            'default_currency_id' => Currency::query()->where('code', 'USD')->firstOrFail()->id,
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'organization_id' => $organization->id,
            'role' => OrganizationRole::Owner,
            'status' => OrganizationMemberStatus::Active,
            'joined_at' => now(),
            'country_id' => $country->id,
        ]);
    }
}
