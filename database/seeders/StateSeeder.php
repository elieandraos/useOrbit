<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Country;
use App\Models\State;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

final class StateSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $countryIdsByIso2 = Country::query()->pluck('id', 'iso2');
        $now = Carbon::now();

        $states = collect(File::json(database_path('data/states.json')))
            ->filter(fn (array $state): bool => $countryIdsByIso2->has($state['country_iso2']))
            ->map(fn (array $state): array => [
                'name' => $state['name'],
                'country_id' => $countryIdsByIso2[$state['country_iso2']],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        $states->chunk(500)->each(fn ($chunk) => State::query()->insert($chunk->all()));
    }
}
