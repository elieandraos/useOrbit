<?php

declare(strict_types=1);

use App\Enums\CarrierStatus;
use App\Filters\CarrierFilter;
use App\Models\Carrier;
use App\Models\Scopes\CurrentOrganizationScope;

test('empty filters return the unfiltered builder', function () {
    Carrier::factory(3)->create();

    /** @noinspection PhpUndefinedMethodInspection */
    $carriers = Carrier::query()->withoutGlobalScope(CurrentOrganizationScope::class)->filter(new CarrierFilter([]))->get();

    expect($carriers)->toHaveCount(3);
});

test('a null or empty string value is skipped', function () {
    Carrier::factory(3)->create();

    /** @noinspection PhpUndefinedMethodInspection */
    $carriers = Carrier::query()->withoutGlobalScope(CurrentOrganizationScope::class)->filter(new CarrierFilter(['search' => null]))->get();

    expect($carriers)->toHaveCount(3);
});

test('an unrecognized filter key is ignored', function () {
    Carrier::factory(3)->create();

    /** @noinspection PhpUndefinedMethodInspection */
    $carriers = Carrier::query()->withoutGlobalScope(CurrentOrganizationScope::class)->filter(new CarrierFilter(['unknown' => 'value']))->get();

    expect($carriers)->toHaveCount(3);
});

test('search matches name', function () {
    /** @var Carrier $match */
    $match = Carrier::factory()->create(['name' => 'Alpha Assurance']);
    Carrier::factory()->create(['name' => 'Bravo Insurance', 'phone' => '+96170999999', 'website' => 'bravo.com']);

    /** @noinspection PhpUndefinedMethodInspection */
    $carriers = Carrier::query()->withoutGlobalScope(CurrentOrganizationScope::class)->filter(new CarrierFilter(['search' => 'Alpha']))->get();

    expect($carriers->pluck('id')->all())->toBe([$match->id]);
});

test('search matches phone', function () {
    /** @var Carrier $match */
    $match = Carrier::factory()->create(['phone' => '+96170123456']);
    Carrier::factory()->create(['phone' => '+96170999999']);

    /** @noinspection PhpUndefinedMethodInspection */
    $carriers = Carrier::query()->withoutGlobalScope(CurrentOrganizationScope::class)->filter(new CarrierFilter(['search' => '70123456']))->get();

    expect($carriers->pluck('id')->all())->toBe([$match->id]);
});

test('search matches website', function () {
    /** @var Carrier $match */
    $match = Carrier::factory()->create(['website' => 'alpha-assurance.com']);
    Carrier::factory()->create(['website' => 'bravo-insurance.com']);

    /** @noinspection PhpUndefinedMethodInspection */
    $carriers = Carrier::query()->withoutGlobalScope(CurrentOrganizationScope::class)->filter(new CarrierFilter(['search' => 'alpha-assurance']))->get();

    expect($carriers->pluck('id')->all())->toBe([$match->id]);
});

test('search excludes non-matching carriers', function () {
    Carrier::factory()->create(['name' => 'Alpha Assurance', 'phone' => '+96170123456', 'website' => 'alpha.com']);

    /** @noinspection PhpUndefinedMethodInspection */
    $carriers = Carrier::query()->withoutGlobalScope(CurrentOrganizationScope::class)->filter(new CarrierFilter(['search' => 'nonexistent']))->get();

    expect($carriers)->toHaveCount(0);
});

test('archived=false returns only active carriers', function () {
    /** @var Carrier $active */
    $active = Carrier::factory()->create(['status' => CarrierStatus::Active->value]);
    Carrier::factory()->archived()->create();

    /** @noinspection PhpUndefinedMethodInspection */
    $carriers = Carrier::query()->withoutGlobalScope(CurrentOrganizationScope::class)->filter(new CarrierFilter(['archived' => false]))->get();

    expect($carriers->pluck('id')->all())->toBe([$active->id]);
});

test('archived=true returns only archived carriers', function () {
    Carrier::factory()->create(['status' => CarrierStatus::Active->value]);
    /** @var Carrier $archived */
    $archived = Carrier::factory()->archived()->create();

    /** @noinspection PhpUndefinedMethodInspection */
    $carriers = Carrier::query()->withoutGlobalScope(CurrentOrganizationScope::class)->filter(new CarrierFilter(['archived' => true]))->get();

    expect($carriers->pluck('id')->all())->toBe([$archived->id]);
});
