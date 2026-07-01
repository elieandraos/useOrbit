<?php

declare(strict_types=1);

use App\Actions\Clients\CreateClientAction;
use App\Enums\ClientStatus;
use App\Enums\Gender;
use App\Enums\LeadSource;
use App\Models\User;

$attributes = [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'phone' => '555-0100',
    'date_of_birth' => '1990-01-15',
    'gender' => Gender::Male->value,
    'enrollment_date' => '2024-01-01',
    'lead_source' => LeadSource::Referral->value,
];

test('sets status to active by default', function () use ($attributes) {
    $user = User::factory()->withOrganization()->create();

    $client = app(CreateClientAction::class)->handle($user, $attributes);

    expect($client->status)->toBe(ClientStatus::Active);
});

test('creates client scoped to the user current organization', function () use ($attributes) {
    $user = User::factory()->withOrganization()->create();

    $client = app(CreateClientAction::class)->handle($user, $attributes);

    expect($client->organization_id)->toBe($user->current_organization_id);
});

test('sets created_by to the user id', function () use ($attributes) {
    $user = User::factory()->withOrganization()->create();

    $client = app(CreateClientAction::class)->handle($user, $attributes);

    expect($client->created_by)->toBe($user->id);
});

test('generates a non-empty slug', function () use ($attributes) {
    $user = User::factory()->withOrganization()->create();

    $client = app(CreateClientAction::class)->handle($user, $attributes);

    expect($client->slug)->not->toBeEmpty();
});

test('two organizations can each have the same slug without collision', function () use ($attributes) {
    $userA = User::factory()->withOrganization()->create();
    $userB = User::factory()->withOrganization()->create();

    $clientA = app(CreateClientAction::class)->handle($userA, $attributes);
    $clientB = app(CreateClientAction::class)->handle($userB, $attributes);

    expect($clientA->slug)->toBe('john-doe')
        ->and($clientB->slug)->toBe('john-doe');
});

test('appends counter when slug already exists in the same organization', function () use ($attributes) {
    $user = User::factory()->withOrganization()->create();

    $first = app(CreateClientAction::class)->handle($user, $attributes);
    $second = app(CreateClientAction::class)->handle($user, $attributes);

    expect($first->slug)->toBe('john-doe')
        ->and($second->slug)->toBe('john-doe-1');
});
