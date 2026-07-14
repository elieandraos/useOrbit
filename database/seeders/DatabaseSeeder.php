<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Nnjeim\World\Models\Country;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (Country::query()->doesntExist()) {
            $this->call(WorldSeeder::class);
        }

        if (app()->environment('local')) {
            $this->call(UserSeeder::class);
            $this->call(ClientsSeeder::class);
        }
    }
}
