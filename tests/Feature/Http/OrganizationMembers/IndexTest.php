<?php

declare(strict_types=1);

use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Http\Resources\OrganizationMemberResource;
use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('organization-members.index'))
        ->assertRedirect(route('login'));
});

test('renders the roster for the current organization, exposing id, name, email, role, status, joined_at and is_you', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create(['name' => 'Amanda Owner']);
    $member = User::factory()->forOrganization($organization)->create(['name' => 'Zack Member']);

    $this->actingAs($owner)
        ->get(route('organization-members.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('OrganizationMembers/Index'))
        ->assertHasResource(
            'members',
            OrganizationMemberResource::collection($organization->users()->orderBy('users.name')->get())
        )
        ->assertInertia(fn ($page) => $page
            ->has('members', 2)
            ->where('members.0.id', $owner->id)
            ->where('members.0.is_you', true)
            ->where('members.1.id', $member->id)
            ->where('members.1.is_you', false)
        );
});

test('roster includes invited members alongside active members', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $invitee = User::factory()->create(['password' => null, 'name' => 'Zzz Invitee']);
    $invitee->organizations()->attach($organization, [
        'role' => OrganizationRole::Member->value,
        'status' => OrganizationMemberStatus::Invited->value,
    ]);

    $this->actingAs($owner)
        ->get(route('organization-members.index'))
        ->assertInertia(fn ($page) => $page
            ->where('members.1.id', $invitee->id)
            ->where('members.1.status', OrganizationMemberStatus::Invited->value)
        );
});

test('roster does not include members from another organization', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $otherOrgUser = User::factory()->withOrganization()->create();

    $this->actingAs($owner)
        ->get(route('organization-members.index'))
        ->assertInertia(fn ($page) => $page->has('members', 1));
});

test('exposes the invitable role options for the invite member form', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    $this->actingAs($owner)
        ->get(route('organization-members.index'))
        ->assertInertia(fn ($page) => $page
            ->where('roleOptions', OrganizationRole::invitableOptions())
        );
});
