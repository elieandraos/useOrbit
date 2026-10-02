<?php

declare(strict_types=1);

use App\Models\Currency;
use App\Models\Organization;
use Illuminate\Database\QueryException;

test('deleting a currency an organization defaults to fails', function () {
    $currency = Currency::factory()->create();
    Organization::factory()->create(['default_currency_id' => $currency->id]);

    $currency->delete();
})->throws(QueryException::class);
