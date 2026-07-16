<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

final class CountrySeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $now = Carbon::now();

        $countries = collect(File::json(database_path('data/countries.json')))
            ->map(fn (array $country): array => [...$country, 'created_at' => $now, 'updated_at' => $now]);

        $countries->chunk(100)->each(fn ($chunk) => Country::query()->insert($chunk->all()));
    }
}
