<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

final class CurrencySeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $now = Carbon::now();

        $currencies = collect(File::json(database_path('data/currencies.json')))
            ->map(fn (array $currency): array => [...$currency, 'created_at' => $now, 'updated_at' => $now]);

        Currency::query()->insert($currencies->all());
    }
}
