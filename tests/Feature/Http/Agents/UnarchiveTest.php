<?php

declare(strict_types=1);

use App\Enums\AgentStatus;
use App\Enums\OrganizationRole;
use App\Models\Agent;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\ResourceUnarchivedNotification;
use Illuminate\Support\Facades\Notification;

test('guests are redirected to the login page', function () {
    $agent = Agent::factory()->archived()->create();

    $this->patch(route('agents.unarchive', $agent))
        ->assertRedirect(route('login'));
});

test('owner can unarchive an agent from their organization', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $agent = Agent::factory()->forOrganization($owner)->archived()->create();

    $this->actingAs($owner)
        ->patch(route('agents.unarchive', $agent))
        ->assertRedirect(route('agents.index'))
        ->assertHasInertiaFlash('success', 'Agent unarchived.');

    /** @var Agent $fresh */
    $fresh = $agent->fresh();
    expect($fresh->status)->toBe(AgentStatus::Active);
});

test('non-owner member is forbidden from unarchiving an agent', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->archived()->create();

    $this->actingAs($user)
        ->patch(route('agents.unarchive', $agent))
        ->assertForbidden();
});

test('notifies leadership that the agent was unarchived, excluding the actor and plain members', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $admin = User::factory()->forOrganization($organization, OrganizationRole::Admin)->create();
    $member = User::factory()->forOrganization($organization, OrganizationRole::Member)->create();
    $agent = Agent::factory()->forOrganization($owner)->archived()->create();

    $this->actingAs($owner)->patch(route('agents.unarchive', $agent));

    Notification::assertSentTo(
        $admin,
        ResourceUnarchivedNotification::class,
        fn (ResourceUnarchivedNotification $notification): bool => $notification->toArray($admin)['subject']['kind'] === 'agent',
    );
    Notification::assertNotSentTo($owner, ResourceUnarchivedNotification::class);
    Notification::assertNotSentTo($member, ResourceUnarchivedNotification::class);
});
