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
    'status' => ClientStatus::Active->value,
];

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
