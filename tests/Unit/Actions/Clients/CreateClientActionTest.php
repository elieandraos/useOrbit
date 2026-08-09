<?php

declare(strict_types=1);

use App\Actions\Clients\CreateClientAction;
use App\Enums\ClientStatus;
use App\Enums\ClientType;
use App\Enums\Gender;
use App\Enums\LeadSource;
use App\Models\User;

$attributes = [
    'client_type' => ClientType::Individual->value,
    'first_name' => 'John',
    'last_name' => 'Doe',
    'phone' => '555-0100',
    'date_of_birth' => '1990-01-15',
    'gender' => Gender::Male->value,
    'enrollment_date' => '2024-01-01',
    'lead_source' => LeadSource::Referral->value,
];

$companyAttributes = [
    'client_type' => ClientType::Company->value,
    'company_name' => 'Acme Logistics',
    'first_name' => 'Rita',
    'last_name' => 'Haddad',
    'phone' => '555-0300',
    'email' => 'rita@acme.test',
    'enrollment_date' => '2024-01-01',
    'lead_source' => LeadSource::Website->value,
];

test('sets status to active by default', function () use ($attributes) {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);

    /** @noinspection PhpUnhandledExceptionInspection */
    $client = app(CreateClientAction::class)->handle($user, $attributes);

    expect($client->status)->toBe(ClientStatus::Active);
});

test('creates client scoped to the user current organization', function () use ($attributes) {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);

    /** @noinspection PhpUnhandledExceptionInspection */
    $client = app(CreateClientAction::class)->handle($user, $attributes);

    expect($client->organization_id)->toBe($user->organization_id);
});

test('sets created_by to the user id', function () use ($attributes) {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);

    /** @noinspection PhpUnhandledExceptionInspection */
    $client = app(CreateClientAction::class)->handle($user, $attributes);

    expect($client->created_by)->toBe($user->id);
});

test('generates a non-empty slug', function () use ($attributes) {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);

    /** @noinspection PhpUnhandledExceptionInspection */
    $client = app(CreateClientAction::class)->handle($user, $attributes);

    expect($client->slug)->not->toBeEmpty();
});

test('two organizations can each have the same slug without collision', function () use ($attributes) {
    $userA = User::factory()->withOrganization()->create();
    $userB = User::factory()->withOrganization()->create();

    setOrganizationContext($userA);
    /** @noinspection PhpUnhandledExceptionInspection */
    $clientA = app(CreateClientAction::class)->handle($userA, $attributes);

    setOrganizationContext($userB);
    /** @noinspection PhpUnhandledExceptionInspection */
    $clientB = app(CreateClientAction::class)->handle($userB, $attributes);

    expect($clientA->slug)->toBe('john-doe')
        ->and($clientB->slug)->toBe('john-doe');
});

test('appends counter when slug already exists in the same organization', function () use ($attributes) {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);

    /** @noinspection PhpUnhandledExceptionInspection */
    $first = app(CreateClientAction::class)->handle($user, $attributes);
    /** @noinspection PhpUnhandledExceptionInspection */
    $second = app(CreateClientAction::class)->handle($user, $attributes);

    expect($first->slug)->toBe('john-doe')
        ->and($second->slug)->toBe('john-doe-1');
});

test('generates slug from company_name for a company client', function () use ($companyAttributes) {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);

    /** @noinspection PhpUnhandledExceptionInspection */
    $client = app(CreateClientAction::class)->handle($user, $companyAttributes);

    expect($client->slug)->toBe('acme-logistics');
});

test('two organizations can each have the same company slug without collision', function () use ($companyAttributes) {
    $userA = User::factory()->withOrganization()->create();
    $userB = User::factory()->withOrganization()->create();

    setOrganizationContext($userA);
    /** @noinspection PhpUnhandledExceptionInspection */
    $clientA = app(CreateClientAction::class)->handle($userA, $companyAttributes);

    setOrganizationContext($userB);
    /** @noinspection PhpUnhandledExceptionInspection */
    $clientB = app(CreateClientAction::class)->handle($userB, $companyAttributes);

    expect($clientA->slug)->toBe('acme-logistics')
        ->and($clientB->slug)->toBe('acme-logistics');
});
