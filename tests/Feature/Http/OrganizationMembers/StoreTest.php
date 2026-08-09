<?php

declare(strict_types=1);

use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

$validPayload = [
    'name' => 'Jane Doe',
    'email' => 'jane.doe@useorbit.com',
    'role' => 'member',
];

test('guests are redirected to the login page', function () use ($validPayload) {
    $this->post(route('organization-members.store'), $validPayload)
        ->assertRedirect(route('login'));
});

test('member cannot invite members', function () use ($validPayload) {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->post(route('organization-members.store'), $validPayload)
        ->assertForbidden();
});

test('store returns validation errors when required fields are missing', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    $this->actingAs($user)
        ->post(route('organization-members.store'))
        ->assertSessionHasErrors(['name', 'email', 'role']);
});

test('store rejects a role outside admin or member', function () use ($validPayload) {
    $organization = Organization::factory()->create();
    $user = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    $this->actingAs($user)
        ->post(route('organization-members.store'), [...$validPayload, 'role' => 'owner'])
        ->assertSessionHasErrors(['role']);
});

test('store rejects an email already a member of this organization', function () use ($validPayload) {
    $organization = Organization::factory()->create();
    $user = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    User::factory()->forOrganization($organization)->create(['email' => $validPayload['email']]);

    $this->actingAs($user)
        ->post(route('organization-members.store'), $validPayload)
        ->assertInvalid(['email' => 'already a member of this organization']);
});

test('store rejects an email already invited to this organization', function () use ($validPayload) {
    $organization = Organization::factory()->create();
    $user = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    User::factory()->forOrganization($organization)->create([
        'email' => $validPayload['email'],
        'password' => null,
        'status' => OrganizationMemberStatus::Invited,
    ]);

    $this->actingAs($user)
        ->post(route('organization-members.store'), $validPayload)
        ->assertInvalid(['email' => 'already a member of this organization']);
});

test('store rejects an email registered outside this organization', function () use ($validPayload) {
    $organization = Organization::factory()->create();
    $user = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    User::factory()->withOrganization()->create(['email' => $validPayload['email']]);

    $this->actingAs($user)
        ->post(route('organization-members.store'), $validPayload)
        ->assertInvalid(['email' => 'already registered']);
});

test('store redirects with a success toast on the happy path', function () use ($validPayload) {
    $organization = Organization::factory()->create();
    $user = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    $this->actingAs($user)
        ->post(route('organization-members.store'), $validPayload)
        ->assertRedirect()
        ->assertHasInertiaFlash('success', 'Invitation sent.');

    expect(User::query()->where('email', $validPayload['email'])->exists())->toBeTrue();
});
