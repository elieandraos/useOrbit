<?php

declare(strict_types=1);

use App\Actions\Clients\UpdateClientAction;
use App\Enums\ClientStatus;
use App\Enums\Gender;
use App\Enums\LeadSource;
use App\Models\Client;
use App\Models\User;

$attributes = [
    'first_name' => 'Jane',
    'last_name' => 'Smith',
    'phone' => '555-0200',
    'date_of_birth' => '1992-05-20',
    'gender' => Gender::Female->value,
    'enrollment_date' => '2024-06-01',
    'lead_source' => LeadSource::Referral->value,
    'status' => ClientStatus::Active->value,
];

test('updates the client fields in the database', function () use ($attributes) {
    $user = User::factory()->withOrganization()->create();
    /** @var Client $client */
    $client = Client::factory()->forOrganization($user)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdateClientAction::class)->handle($user, $client, $attributes);

    /** @var Client $fresh */
    $fresh = $client->fresh();
    expect($fresh->first_name)->toBe('Jane')
        ->and($fresh->last_name)->toBe('Smith')
        ->and($fresh->phone)->toBe('555-0200');
});

test('sets updated_by to the user id', function () use ($attributes) {
    $user = User::factory()->withOrganization()->create();
    /** @var Client $client */
    $client = Client::factory()->forOrganization($user)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdateClientAction::class)->handle($user, $client, $attributes);

    /** @var Client $fresh */
    $fresh = $client->fresh();
    expect($fresh->updated_by)->toBe($user->id);
});

test('regenerates slug when name changes', function () use ($attributes) {
    $user = User::factory()->withOrganization()->create();
    /** @var Client $client */
    $client = Client::factory()->forOrganization($user)->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'slug' => 'john-doe',
    ]);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdateClientAction::class)->handle($user, $client, $attributes);

    /** @var Client $fresh */
    $fresh = $client->fresh();
    expect($fresh->slug)->toBe('jane-smith');
});

test('keeps existing slug when name does not change', function () use ($attributes) {
    $user = User::factory()->withOrganization()->create();
    /** @var Client $client */
    $client = Client::factory()->forOrganization($user)->create([
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'slug' => 'jane-smith',
    ]);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdateClientAction::class)->handle($user, $client, $attributes);

    /** @var Client $fresh */
    $fresh = $client->fresh();
    expect($fresh->slug)->toBe('jane-smith');
});
