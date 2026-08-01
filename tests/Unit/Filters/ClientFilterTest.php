<?php

declare(strict_types=1);

use App\Enums\ClientStatus;
use App\Enums\ClientType;
use App\Enums\Gender;
use App\Filters\ClientFilter;
use App\Models\Client;

test('empty filters return the unfiltered builder', function () {
    Client::factory(3)->create();

    /** @noinspection PhpUndefinedMethodInspection */
    $clients = Client::query()->filter(new ClientFilter([]))->get();

    expect($clients)->toHaveCount(3);
});

test('a null or empty string value is skipped', function () {
    Client::factory(3)->create();

    /** @noinspection PhpUndefinedMethodInspection */
    $clients = Client::query()->filter(new ClientFilter(['search' => null, 'gender' => '']))->get();

    expect($clients)->toHaveCount(3);
});

test('an unrecognized filter key is ignored', function () {
    Client::factory(3)->create();

    /** @noinspection PhpUndefinedMethodInspection */
    $clients = Client::query()->filter(new ClientFilter(['unknown' => 'value']))->get();

    expect($clients)->toHaveCount(3);
});

test('search matches first name', function () {
    /** @var Client $match */
    $match = Client::factory()->create(['first_name' => 'Aline']);
    Client::factory()->create([
        'first_name' => 'Karim',
        'middle_name' => 'Nasser',
        'last_name' => 'Saad',
        'phone' => '+96170999999',
        'email' => 'john@example.com',
    ]);

    /** @noinspection PhpUndefinedMethodInspection */
    $clients = Client::query()->filter(new ClientFilter(['search' => 'Ali']))->get();

    expect($clients->pluck('id')->all())->toBe([$match->id]);
});

test('search matches middle name', function () {
    /** @var Client $match */
    $match = Client::factory()->create(['middle_name' => 'Yusuf']);
    Client::factory()->create([
        'first_name' => 'Karim',
        'middle_name' => 'Nasser',
        'last_name' => 'Saad',
        'phone' => '+96170999999',
        'email' => 'john@example.com',
    ]);

    /** @noinspection PhpUndefinedMethodInspection */
    $clients = Client::query()->filter(new ClientFilter(['search' => 'Yusuf']))->get();

    expect($clients->pluck('id')->all())->toBe([$match->id]);
});

test('search matches last name', function () {
    /** @var Client $match */
    $match = Client::factory()->create(['last_name' => 'Haddad']);
    Client::factory()->create([
        'first_name' => 'Karim',
        'middle_name' => 'Nasser',
        'last_name' => 'Saad',
        'phone' => '+96170999999',
        'email' => 'john@example.com',
    ]);

    /** @noinspection PhpUndefinedMethodInspection */
    $clients = Client::query()->filter(new ClientFilter(['search' => 'Haddad']))->get();

    expect($clients->pluck('id')->all())->toBe([$match->id]);
});

test('search matches company name', function () {
    /** @var Client $match */
    $match = Client::factory()->company()->create(['company_name' => 'Acme Logistics']);
    Client::factory()->create([
        'first_name' => 'Karim',
        'middle_name' => 'Nasser',
        'last_name' => 'Saad',
        'phone' => '+96170999999',
        'email' => 'john@example.com',
    ]);

    /** @noinspection PhpUndefinedMethodInspection */
    $clients = Client::query()->filter(new ClientFilter(['search' => 'Acme']))->get();

    expect($clients->pluck('id')->all())->toBe([$match->id]);
});

test('search matches phone', function () {
    /** @var Client $match */
    $match = Client::factory()->create(['phone' => '+96170123456']);
    Client::factory()->create([
        'first_name' => 'Karim',
        'middle_name' => 'Nasser',
        'last_name' => 'Saad',
        'phone' => '+96170999999',
        'email' => 'john@example.com',
    ]);

    /** @noinspection PhpUndefinedMethodInspection */
    $clients = Client::query()->filter(new ClientFilter(['search' => '70123456']))->get();

    expect($clients->pluck('id')->all())->toBe([$match->id]);
});

test('search matches email', function () {
    /** @var Client $match */
    $match = Client::factory()->create(['email' => 'jane@example.com']);
    Client::factory()->create([
        'first_name' => 'Karim',
        'middle_name' => 'Nasser',
        'last_name' => 'Saad',
        'phone' => '+96170999999',
        'email' => 'john@example.com',
    ]);

    /** @noinspection PhpUndefinedMethodInspection */
    $clients = Client::query()->filter(new ClientFilter(['search' => 'jane@']))->get();

    expect($clients->pluck('id')->all())->toBe([$match->id]);
});

test('search excludes non-matching clients', function () {
    Client::factory()->create(['first_name' => 'Aline', 'middle_name' => null, 'last_name' => 'Haddad', 'phone' => '+96170123456', 'email' => 'jane@example.com']);

    /** @noinspection PhpUndefinedMethodInspection */
    $clients = Client::query()->filter(new ClientFilter(['search' => 'nonexistent']))->get();

    expect($clients)->toHaveCount(0);
});

test('clientType narrows to the exact matching type only', function () {
    /** @var Client $match */
    $match = Client::factory()->company()->create();
    Client::factory()->create(['client_type' => ClientType::Individual->value]);

    /** @noinspection PhpUndefinedMethodInspection */
    $clients = Client::query()->filter(new ClientFilter(['client_type' => ClientType::Company->value]))->get();

    expect($clients->pluck('id')->all())->toBe([$match->id]);
});

test('gender narrows to the exact matching value only', function () {
    /** @var Client $match */
    $match = Client::factory()->create(['gender' => Gender::Female->value]);
    Client::factory()->create(['gender' => Gender::Male->value]);

    /** @noinspection PhpUndefinedMethodInspection */
    $clients = Client::query()->filter(new ClientFilter(['gender' => Gender::Female->value]))->get();

    expect($clients->pluck('id')->all())->toBe([$match->id]);
});

test('enrolledFrom is an inclusive lower bound', function () {
    /** @var Client $onBoundary */
    $onBoundary = Client::factory()->create(['enrollment_date' => '2024-01-10']);
    /** @var Client $after */
    $after = Client::factory()->create(['enrollment_date' => '2024-01-15']);
    Client::factory()->create(['enrollment_date' => '2024-01-05']);

    /** @noinspection PhpUndefinedMethodInspection */
    $clients = Client::query()->filter(new ClientFilter(['enrolled_from' => '2024-01-10']))->get();

    expect($clients->pluck('id')->sort()->values()->all())->toBe(collect([$onBoundary->id, $after->id])->sort()->values()->all());
});

test('enrolledTo is an inclusive upper bound', function () {
    /** @var Client $onBoundary */
    $onBoundary = Client::factory()->create(['enrollment_date' => '2024-01-10']);
    /** @var Client $before */
    $before = Client::factory()->create(['enrollment_date' => '2024-01-05']);
    Client::factory()->create(['enrollment_date' => '2024-01-15']);

    /** @noinspection PhpUndefinedMethodInspection */
    $clients = Client::query()->filter(new ClientFilter(['enrolled_to' => '2024-01-10']))->get();

    expect($clients->pluck('id')->sort()->values()->all())->toBe(collect([$onBoundary->id, $before->id])->sort()->values()->all());
});

test('enrolledFrom and enrolledTo combined narrow to the inclusive range', function () {
    /** @var Client $inRange */
    $inRange = Client::factory()->create(['enrollment_date' => '2024-01-10']);
    Client::factory()->create(['enrollment_date' => '2024-01-01']);
    Client::factory()->create(['enrollment_date' => '2024-02-01']);

    /** @noinspection PhpUndefinedMethodInspection */
    $clients = Client::query()->filter(new ClientFilter([
        'enrolled_from' => '2024-01-05',
        'enrolled_to' => '2024-01-20',
    ]))->get();

    expect($clients->pluck('id')->all())->toBe([$inRange->id]);
});

test('ageMin includes a client turning exactly the minimum age today', function () {
    /** @var Client $turnsMinToday */
    $turnsMinToday = Client::factory()->create(['date_of_birth' => now()->subYears(30)->toDateString()]);
    /** @var Client $youngerByOneDay */
    $youngerByOneDay = Client::factory()->create(['date_of_birth' => now()->subYears(30)->addDay()->toDateString()]);

    /** @noinspection PhpUndefinedMethodInspection */
    $clients = Client::query()->filter(new ClientFilter(['age_min' => 30]))->get();

    expect($clients->pluck('id')->all())->toBe([$turnsMinToday->id])
        ->and($clients->pluck('id'))->not->toContain($youngerByOneDay->id);
});

test('ageMax includes a client who has not yet turned max + 1 today', function () {
    /** @var Client $turnsMaxToday */
    $turnsMaxToday = Client::factory()->create(['date_of_birth' => now()->subYears(40)->toDateString()]);
    /** @var Client $turnsMaxPlusOneToday */
    $turnsMaxPlusOneToday = Client::factory()->create(['date_of_birth' => now()->subYears(41)->toDateString()]);

    /** @noinspection PhpUndefinedMethodInspection */
    $clients = Client::query()->filter(new ClientFilter(['age_max' => 40]))->get();

    expect($clients->pluck('id')->all())->toBe([$turnsMaxToday->id])
        ->and($clients->pluck('id'))->not->toContain($turnsMaxPlusOneToday->id);
});

test('ageMin and ageMax combined narrow to the inclusive age range', function () {
    /** @var Client $inRange */
    $inRange = Client::factory()->create(['date_of_birth' => now()->subYears(35)->toDateString()]);
    Client::factory()->create(['date_of_birth' => now()->subYears(20)->toDateString()]);
    Client::factory()->create(['date_of_birth' => now()->subYears(50)->toDateString()]);

    /** @noinspection PhpUndefinedMethodInspection */
    $clients = Client::query()->filter(new ClientFilter([
        'age_min' => 30,
        'age_max' => 40,
    ]))->get();

    expect($clients->pluck('id')->all())->toBe([$inRange->id]);
});

test('all filters combined narrow to a single matching client', function () {
    /** @var Client $match */
    $match = Client::factory()->create([
        'first_name' => 'Aline',
        'gender' => Gender::Female->value,
        'enrollment_date' => '2024-01-10',
        'date_of_birth' => now()->subYears(30)->toDateString(),
    ]);

    Client::factory()->create([
        'first_name' => 'Aline',
        'gender' => Gender::Male->value,
        'enrollment_date' => '2024-01-10',
        'date_of_birth' => now()->subYears(30)->toDateString(),
    ]);

    /** @noinspection PhpUndefinedMethodInspection */
    $clients = Client::query()->filter(new ClientFilter([
        'search' => 'Aline',
        'gender' => Gender::Female->value,
        'enrolled_from' => '2024-01-01',
        'enrolled_to' => '2024-01-31',
        'age_min' => 25,
        'age_max' => 35,
    ]))->get();

    expect($clients->pluck('id')->all())->toBe([$match->id]);
});

test('archived=false returns only active clients', function () {
    /** @var Client $active */
    $active = Client::factory()->create(['status' => ClientStatus::Active->value]);
    Client::factory()->archived()->create();

    /** @noinspection PhpUndefinedMethodInspection */
    $clients = Client::query()->filter(new ClientFilter(['archived' => false]))->get();

    expect($clients->pluck('id')->all())->toBe([$active->id]);
});

test('archived=true returns only archived clients', function () {
    Client::factory()->create(['status' => ClientStatus::Active->value]);
    /** @var Client $archived */
    $archived = Client::factory()->archived()->create();

    /** @noinspection PhpUndefinedMethodInspection */
    $clients = Client::query()->filter(new ClientFilter(['archived' => true]))->get();

    expect($clients->pluck('id')->all())->toBe([$archived->id]);
});
