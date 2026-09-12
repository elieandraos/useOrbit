<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Country;
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
        if (Country::query()->doesntExist()) {
            $this->call([CountrySeeder::class, StateSeeder::class]);
        }

        if (app()->environment('local')) {
            $this->call(UserSeeder::class);
            $this->call(ClientsSeeder::class);
            $this->call(CarriersSeeder::class);
            $this->call(AgentsSeeder::class);
            $this->call(PoliciesSeeder::class);
            $this->call(NotesSeeder::class);
        }
    }
}
