<?php

declare(strict_types=1);

use App\Actions\Carriers\CreateCarrierAction;
use App\Enums\CarrierStatus;
use App\Models\User;

$attributes = [
    'name' => 'Bankers Assurance',
    'phone' => '+961 1 423 423',
    'website' => 'bankers.com.lb',
    'branch' => [
        'street' => 'Saloumeh Square',
        'building_floor' => 'Bankers Tower',
        'city' => 'Beirut',
    ],
    'contact' => [
        'name' => 'Lina Karam',
        'role' => 'COO',
        'email' => 'lina.karam@bankers.com.lb',
        'phone' => '+961 3 188 422',
    ],
];

test('sets status to active by default', function () use ($attributes) {
    $user = User::factory()->withOrganization()->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $carrier = app(CreateCarrierAction::class)->handle($user, $attributes);

    expect($carrier->status)->toBe(CarrierStatus::Active);
});

test('creates carrier scoped to the user current organization', function () use ($attributes) {
    $user = User::factory()->withOrganization()->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $carrier = app(CreateCarrierAction::class)->handle($user, $attributes);

    expect($carrier->organization_id)->toBe($user->current_organization_id);
});

test('sets created_by and updated_by to the user id', function () use ($attributes) {
    $user = User::factory()->withOrganization()->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $carrier = app(CreateCarrierAction::class)->handle($user, $attributes);

    expect($carrier->created_by)->toBe($user->id)
        ->and($carrier->updated_by)->toBe($user->id);
});

test('generates a non-empty slug', function () use ($attributes) {
    $user = User::factory()->withOrganization()->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $carrier = app(CreateCarrierAction::class)->handle($user, $attributes);

    expect($carrier->slug)->not->toBeEmpty();
});

test('two organizations can each have the same slug without collision', function () use ($attributes) {
    $userA = User::factory()->withOrganization()->create();
    $userB = User::factory()->withOrganization()->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $carrierA = app(CreateCarrierAction::class)->handle($userA, $attributes);
    /** @noinspection PhpUnhandledExceptionInspection */
    $carrierB = app(CreateCarrierAction::class)->handle($userB, $attributes);

    expect($carrierA->slug)->toBe('bankers-assurance')
        ->and($carrierB->slug)->toBe('bankers-assurance');
});

test('appends counter when slug already exists in the same organization', function () use ($attributes) {
    $user = User::factory()->withOrganization()->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $first = app(CreateCarrierAction::class)->handle($user, $attributes);
    /** @noinspection PhpUnhandledExceptionInspection */
    $second = app(CreateCarrierAction::class)->handle($user, $attributes);

    expect($first->slug)->toBe('bankers-assurance')
        ->and($second->slug)->toBe('bankers-assurance-1');
});

test('creates exactly one branch with the submitted branch and contact fields', function () use ($attributes) {
    $user = User::factory()->withOrganization()->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $carrier = app(CreateCarrierAction::class)->handle($user, $attributes);

    expect($carrier->branches)->toHaveCount(1);

    $branch = $carrier->branches->first();
    expect($branch->street)->toBe('Saloumeh Square')
        ->and($branch->building_floor)->toBe('Bankers Tower')
        ->and($branch->city)->toBe('Beirut')
        ->and($branch->contact_name)->toBe('Lina Karam')
        ->and($branch->contact_role)->toBe('COO')
        ->and($branch->contact_email)->toBe('lina.karam@bankers.com.lb')
        ->and($branch->contact_phone)->toBe('+961 3 188 422');
});
