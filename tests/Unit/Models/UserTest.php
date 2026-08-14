<?php

declare(strict_types=1);

use App\Enums\OrganizationMemberStatus;
use App\Models\Country;
use App\Models\Organization;
use App\Models\User;
use App\Support\Tenancy\OrganizationContext;
use LogicException;

test('country resolves the user\'s associated country', function () {
    $country = Country::query()->create([
        'name' => 'Lebanon',
        'iso2' => 'LB',
        'iso3' => 'LBN',
    ]);
    $user = User::factory()->create(['country_id' => $country->id]);

    expect($user->country)->toBeInstanceOf(Country::class)
        ->and($user->country->is($country))->toBeTrue();
});

test('organization resolves the user\'s organization', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->forOrganization($organization)->create();

    expect($user->organization)->toBeInstanceOf(Organization::class)
        ->and($user->organization->is($organization))->toBeTrue();
});

test('inviter resolves the user who sent the invitation', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization)->create();
    $invitee = User::factory()->forOrganization($organization)->create(['invited_by' => $owner->id]);

    expect($invitee->inviter?->is($owner))->toBeTrue();
});

test('inviter is null once the inviting user has been deleted', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization)->create();
    $invitee = User::factory()->forOrganization($organization)->create(['invited_by' => $owner->id]);

    $owner->delete();

    expect($invitee->fresh()->inviter)->toBeNull();
});

test('activeInCurrentOrganization returns an active user scoped to the current organization', function () {
    $organization = Organization::factory()->create();
    $activeMember = User::factory()->forOrganization($organization)->create();
    app(OrganizationContext::class)->set($organization->id);

    $recipients = User::query()->activeInCurrentOrganization()->get();

    expect($recipients)->toHaveCount(1)
        ->and($recipients->first()->id)->toBe($activeMember->id);
});

test('activeInCurrentOrganization excludes an active user from another organization', function () {
    $organization = Organization::factory()->create();
    User::factory()->withOrganization()->create();
    app(OrganizationContext::class)->set($organization->id);

    $recipients = User::query()->activeInCurrentOrganization()->get();

    expect($recipients)->toBeEmpty();
});

test('activeInCurrentOrganization excludes an invited user in the current organization', function () {
    $organization = Organization::factory()->create();
    User::factory()->forOrganization($organization)->create(['status' => OrganizationMemberStatus::Invited]);
    app(OrganizationContext::class)->set($organization->id);

    $recipients = User::query()->activeInCurrentOrganization()->get();

    expect($recipients)->toBeEmpty();
});

test('activeInCurrentOrganization excludes a suspended user in the current organization', function () {
    $organization = Organization::factory()->create();
    User::factory()->forOrganization($organization)->create(['status' => OrganizationMemberStatus::Suspended]);
    app(OrganizationContext::class)->set($organization->id);

    $recipients = User::query()->activeInCurrentOrganization()->get();

    expect($recipients)->toBeEmpty();
});

test('activeInCurrentOrganization fails closed when no organization context has been established', function () {
    User::factory()->withOrganization()->create();

    User::query()->activeInCurrentOrganization()->get();
})->throws(LogicException::class, 'No organization context has been established.');

test('query without the scope remains unscoped across organizations', function () {
    User::factory()->withOrganization()->create();
    User::factory()->withOrganization()->create();

    expect(User::query()->count())->toBe(2);
});
