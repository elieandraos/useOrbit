<?php

declare(strict_types=1);

use App\Models\Country;
use App\Models\Organization;
use App\Models\User;

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
