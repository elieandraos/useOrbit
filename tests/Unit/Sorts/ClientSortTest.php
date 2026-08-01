<?php

declare(strict_types=1);

use App\Models\Client;
use App\Sorts\ClientSort;

test('name sorts by first name then last name', function () {
    /** @var Client $bravo */
    $bravo = Client::factory()->create(['first_name' => 'Bravo', 'last_name' => 'Zulu']);
    /** @var Client $alphaZulu */
    $alphaZulu = Client::factory()->create(['first_name' => 'Alpha', 'last_name' => 'Zulu']);
    /** @var Client $alphaAlpha */
    $alphaAlpha = Client::factory()->create(['first_name' => 'Alpha', 'last_name' => 'Alpha']);

    /** @noinspection PhpUndefinedMethodInspection */
    $clients = Client::query()->sort(new ClientSort('name', 'asc'))->get();

    expect($clients->pluck('id')->all())->toBe([$alphaAlpha->id, $alphaZulu->id, $bravo->id]);
});

test('name sort direction can be reversed', function () {
    /** @var Client $bravo */
    $bravo = Client::factory()->create(['first_name' => 'Bravo', 'last_name' => 'Zulu']);
    /** @var Client $alpha */
    $alpha = Client::factory()->create(['first_name' => 'Alpha', 'last_name' => 'Zulu']);

    /** @noinspection PhpUndefinedMethodInspection */
    $clients = Client::query()->sort(new ClientSort('name', 'desc'))->get();

    expect($clients->pluck('id')->all())->toBe([$bravo->id, $alpha->id]);
});

test('name sorts a mixed individual/company set by the displayed name', function () {
    /** @var Client $acme */
    $acme = Client::factory()->company()->create(['company_name' => 'Acme Logistics']);
    /** @var Client $bravo */
    $bravo = Client::factory()->create(['first_name' => 'Bravo', 'last_name' => 'Zulu']);
    /** @var Client $zenith */
    $zenith = Client::factory()->company()->create(['company_name' => 'Zenith Traders']);

    /** @noinspection PhpUndefinedMethodInspection */
    $clients = Client::query()->sort(new ClientSort('name', 'asc'))->get();

    expect($clients->pluck('id')->all())->toBe([$acme->id, $bravo->id, $zenith->id]);
});

test('type sorts individual before company alphabetically', function () {
    /** @var Client $company */
    $company = Client::factory()->company()->create();
    /** @var Client $individual */
    $individual = Client::factory()->create();

    /** @noinspection PhpUndefinedMethodInspection */
    $clients = Client::query()->sort(new ClientSort('type', 'asc'))->get();

    expect($clients->pluck('id')->all())->toBe([$company->id, $individual->id]);
});

test('type sort direction can be reversed', function () {
    /** @var Client $company */
    $company = Client::factory()->company()->create();
    /** @var Client $individual */
    $individual = Client::factory()->create();

    /** @noinspection PhpUndefinedMethodInspection */
    $clients = Client::query()->sort(new ClientSort('type', 'desc'))->get();

    expect($clients->pluck('id')->all())->toBe([$individual->id, $company->id]);
});

test('enrollmentDate sorts chronologically', function () {
    /** @var Client $newest */
    $newest = Client::factory()->create(['enrollment_date' => '2024-06-01']);
    /** @var Client $oldest */
    $oldest = Client::factory()->create(['enrollment_date' => '2023-01-01']);

    /** @noinspection PhpUndefinedMethodInspection */
    $clients = Client::query()->sort(new ClientSort('enrollment_date', 'asc'))->get();

    expect($clients->pluck('id')->all())->toBe([$oldest->id, $newest->id]);
});

test('default falls back to enrollment date, newest first', function () {
    /** @var Client $newest */
    $newest = Client::factory()->create(['enrollment_date' => '2024-06-01']);
    /** @var Client $oldest */
    $oldest = Client::factory()->create(['enrollment_date' => '2023-01-01']);

    /** @noinspection PhpUndefinedMethodInspection */
    $clients = Client::query()->sort(new ClientSort(null, 'asc'))->get();

    expect($clients->pluck('id')->all())->toBe([$newest->id, $oldest->id]);
});

test('an unrecognized column falls back to the default', function () {
    /** @var Client $newest */
    $newest = Client::factory()->create(['enrollment_date' => '2024-06-01']);
    /** @var Client $oldest */
    $oldest = Client::factory()->create(['enrollment_date' => '2023-01-01']);

    /** @noinspection PhpUndefinedMethodInspection */
    $clients = Client::query()->sort(new ClientSort('unknown', 'asc'))->get();

    expect($clients->pluck('id')->all())->toBe([$newest->id, $oldest->id]);
});
