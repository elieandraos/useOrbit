<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\Scopes\CurrentOrganizationScope;
use App\Sorts\CarrierSort;

test('name sorts alphabetically', function () {
    /** @var Carrier $bravo */
    $bravo = Carrier::factory()->create(['name' => 'Bravo Assurance']);
    /** @var Carrier $alpha */
    $alpha = Carrier::factory()->create(['name' => 'Alpha Assurance']);

    /** @noinspection PhpUndefinedMethodInspection */
    $carriers = Carrier::query()->withoutGlobalScope(CurrentOrganizationScope::class)->sort(new CarrierSort('name', 'asc'))->get();

    expect($carriers->pluck('id')->all())->toBe([$alpha->id, $bravo->id]);
});

test('name sort direction can be reversed', function () {
    /** @var Carrier $bravo */
    $bravo = Carrier::factory()->create(['name' => 'Bravo Assurance']);
    /** @var Carrier $alpha */
    $alpha = Carrier::factory()->create(['name' => 'Alpha Assurance']);

    /** @noinspection PhpUndefinedMethodInspection */
    $carriers = Carrier::query()->withoutGlobalScope(CurrentOrganizationScope::class)->sort(new CarrierSort('name', 'desc'))->get();

    expect($carriers->pluck('id')->all())->toBe([$bravo->id, $alpha->id]);
});

test('default falls back to name ascending', function () {
    /** @var Carrier $bravo */
    $bravo = Carrier::factory()->create(['name' => 'Bravo Assurance']);
    /** @var Carrier $alpha */
    $alpha = Carrier::factory()->create(['name' => 'Alpha Assurance']);

    /** @noinspection PhpUndefinedMethodInspection */
    $carriers = Carrier::query()->withoutGlobalScope(CurrentOrganizationScope::class)->sort(new CarrierSort(null, 'asc'))->get();

    expect($carriers->pluck('id')->all())->toBe([$alpha->id, $bravo->id]);
});

test('an unrecognized column falls back to the default', function () {
    /** @var Carrier $bravo */
    $bravo = Carrier::factory()->create(['name' => 'Bravo Assurance']);
    /** @var Carrier $alpha */
    $alpha = Carrier::factory()->create(['name' => 'Alpha Assurance']);

    /** @noinspection PhpUndefinedMethodInspection */
    $carriers = Carrier::query()->withoutGlobalScope(CurrentOrganizationScope::class)->sort(new CarrierSort('unknown', 'asc'))->get();

    expect($carriers->pluck('id')->all())->toBe([$alpha->id, $bravo->id]);
});
