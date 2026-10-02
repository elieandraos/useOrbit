<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\File;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Seeded codes are never generated, because seeding and the `firstOrCreate` factory states own those rows.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $seededCodes = array_column(File::json(database_path('data/currencies.json')), 'code');

        do {
            $code = fake()->unique()->currencyCode();
        } while (in_array($code, $seededCodes, true));

        return [
            'code' => $code,
            'name' => ucwords(fake()->words(2, true)),
        ];
    }
}
