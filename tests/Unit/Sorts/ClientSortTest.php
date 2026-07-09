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

test('email sorts alphabetically', function () {
    /** @var Client $zebra */
    $zebra = Client::factory()->create(['email' => 'zebra@example.com']);
    /** @var Client $apple */
    $apple = Client::factory()->create(['email' => 'apple@example.com']);

    /** @noinspection PhpUndefinedMethodInspection */
    $clients = Client::query()->sort(new ClientSort('email', 'asc'))->get();

    expect($clients->pluck('id')->all())->toBe([$apple->id, $zebra->id]);
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
