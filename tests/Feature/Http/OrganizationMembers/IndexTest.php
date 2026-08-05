<?php

declare(strict_types=1);

use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('organization-members.index'))
        ->assertRedirect(route('login'));
});

test('index returns the roster for the current organization', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();

    $response = $this->actingAs($owner)
        ->get(route('organization-members.index'))
        ->assertOk();

    $ids = collect($response->json('data'))->pluck('id');

    expect($ids)->toHaveCount(2)
        ->and($ids)->toContain($owner->id, $member->id);
});

test('roster entry exposes id, name, email, role, status, joined_at and is_you', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    $response = $this->actingAs($owner)
        ->get(route('organization-members.index'))
        ->assertOk();

    $entry = collect($response->json('data'))->firstWhere('id', $owner->id);

    expect($entry)->toMatchArray([
        'id' => $owner->id,
        'name' => $owner->name,
        'email' => $owner->email,
        'role' => OrganizationRole::Owner->value,
        'status' => OrganizationMemberStatus::Active->value,
        'is_you' => true,
    ])
        ->and($entry)->toHaveKey('joined_at')
        ->and($entry)->not->toHaveKey('token')
        ->and($entry)->not->toHaveKey('expires_at');
});

test('roster includes invited members alongside active members', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $invitee = User::factory()->create(['password' => null]);
    $invitee->organizations()->attach($organization, [
        'role' => OrganizationRole::Member->value,
        'status' => OrganizationMemberStatus::Invited->value,
    ]);

    $response = $this->actingAs($owner)
        ->get(route('organization-members.index'))
        ->assertOk();

    $entry = collect($response->json('data'))->firstWhere('id', $invitee->id);

    expect($entry['status'])->toBe(OrganizationMemberStatus::Invited->value);
});

test('roster does not include members from another organization', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $otherOrgUser = User::factory()->withOrganization()->create();

    $response = $this->actingAs($owner)
        ->get(route('organization-members.index'))
        ->assertOk();

    $ids = collect($response->json('data'))->pluck('id');

    expect($ids)->not->toContain($otherOrgUser->id);
});

test('roster distinguishes the current user with is_you', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();

    $response = $this->actingAs($owner)
        ->get(route('organization-members.index'))
        ->assertOk();

    $entries = collect($response->json('data'))->keyBy('id');

    expect($entries[$owner->id]['is_you'])->toBeTrue()
        ->and($entries[$member->id]['is_you'])->toBeFalse();
});
