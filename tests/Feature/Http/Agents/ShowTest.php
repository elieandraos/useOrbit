<?php

declare(strict_types=1);

use App\Enums\PolicyStatus;
use App\Http\Resources\AgentResource;
use App\Models\Agent;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $agent = Agent::factory()->create();

    $this->get(route('agents.show', $agent))
        ->assertRedirect(route('login'));
});

test('authenticated user can view an agent from their organization', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->get(route('agents.show', $agent))
        ->assertOk()
        ->assertHasResource('agent', AgentResource::make($agent->load(['country', 'state'])));
});

test('authenticated user gets 404 for an agent from another organization', function () {
    $user = User::factory()->withOrganization()->create();

    $otherOrganization = Organization::factory()->create();
    $agent = Agent::factory()->for($otherOrganization)->create();

    $this->actingAs($user)
        ->get(route('agents.show', $agent))
        ->assertNotFound();
});

test('an archived agent can still be shown', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->archived()->create();

    $this->actingAs($user)
        ->get(route('agents.show', $agent))
        ->assertOk();
});

test('policiesCount reflects only the policies written through this agent', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->create();
    Policy::factory()->forOrganization($user)->count(2)->create([
        'agent_id' => $agent->id,
        'created_by' => $user->id,
    ]);

    $otherAgent = Agent::factory()->forOrganization($user)->create();
    Policy::factory()->forOrganization($user)->create([
        'agent_id' => $otherAgent->id,
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('agents.show', $agent))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('policiesCount', 2));
});

test('renewingPolicies lists only this agent\'s active policies expiring within the renewal window', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->create();

    $renewing = Policy::factory()->forOrganization($user)->create([
        'agent_id' => $agent->id,
        'created_by' => $user->id,
        'status' => PolicyStatus::Active->value,
        'expiry_date' => now()->addDays(10)->toDateString(),
    ]);

    // Outside the renewal window.
    Policy::factory()->forOrganization($user)->create([
        'agent_id' => $agent->id,
        'created_by' => $user->id,
        'status' => PolicyStatus::Active->value,
        'expiry_date' => now()->addDays(60)->toDateString(),
    ]);

    // Already expired.
    Policy::factory()->forOrganization($user)->create([
        'agent_id' => $agent->id,
        'created_by' => $user->id,
        'status' => PolicyStatus::Active->value,
        'expiry_date' => now()->subDays(2)->toDateString(),
    ]);

    // Within the window but cancelled.
    Policy::factory()->forOrganization($user)->create([
        'agent_id' => $agent->id,
        'created_by' => $user->id,
        'status' => PolicyStatus::Cancelled->value,
        'expiry_date' => now()->addDays(5)->toDateString(),
    ]);

    // Within the window but written through another agent.
    $otherAgent = Agent::factory()->forOrganization($user)->create();
    Policy::factory()->forOrganization($user)->create([
        'agent_id' => $otherAgent->id,
        'created_by' => $user->id,
        'status' => PolicyStatus::Active->value,
        'expiry_date' => now()->addDays(5)->toDateString(),
    ]);

    $this->actingAs($user)
        ->get(route('agents.show', $agent))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('renewingPolicies', 1)
            ->where('renewingPolicies.0.id', $renewing->id)
        );
});

test('renewingPolicies includes both edges of the renewal window', function (?string $timezone) {
    $this->travelTo('2026-01-15 12:00:00');
    $organization = Organization::factory()->create(['timezone' => $timezone]);
    $user = User::factory()->forOrganization($organization)->create();
    $agent = Agent::factory()->forOrganization($user)->create();

    $expiringToday = Policy::factory()->forOrganization($user)->create([
        'agent_id' => $agent->id,
        'created_by' => $user->id,
        'status' => PolicyStatus::Active->value,
        'expiry_date' => now()->toDateString(),
    ]);
    $expiringAtWindowEdge = Policy::factory()->forOrganization($user)->create([
        'agent_id' => $agent->id,
        'created_by' => $user->id,
        'status' => PolicyStatus::Active->value,
        'expiry_date' => now()->addDays(30)->toDateString(),
    ]);
    // One day past the window.
    Policy::factory()->forOrganization($user)->create([
        'agent_id' => $agent->id,
        'created_by' => $user->id,
        'status' => PolicyStatus::Active->value,
        'expiry_date' => now()->addDays(31)->toDateString(),
    ]);

    $this->actingAs($user)
        ->get(route('agents.show', $agent))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('renewingPolicies', 2)
            ->where('renewingPolicies.0.id', $expiringToday->id)
            ->where('renewingPolicies.1.id', $expiringAtWindowEdge->id)
        );
})->with([
    'no organization timezone' => [null],
    'east of UTC' => ['Asia/Beirut'],
    'west of UTC' => ['America/New_York'],
]);

test('renewingPolicies measures its window from the organization-local date', function (string $instant, string $timezone, string $includedToday, string $includedEdge, string $excludedBefore, string $excludedAfter) {
    $this->travelTo($instant);
    $organization = Organization::factory()->create(['timezone' => $timezone]);
    $user = User::factory()->forOrganization($organization)->create();
    $agent = Agent::factory()->forOrganization($user)->create();

    $createActivePolicyExpiringOn = fn (string $expiryDate): Policy => Policy::factory()->forOrganization($user)->create([
        'agent_id' => $agent->id,
        'created_by' => $user->id,
        'status' => PolicyStatus::Active->value,
        'expiry_date' => $expiryDate,
    ]);

    $expiringLocalToday = $createActivePolicyExpiringOn($includedToday);
    $expiringAtLocalWindowEdge = $createActivePolicyExpiringOn($includedEdge);
    $createActivePolicyExpiringOn($excludedBefore);
    $createActivePolicyExpiringOn($excludedAfter);

    $this->actingAs($user)
        ->get(route('agents.show', $agent))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('renewingPolicies', 2)
            ->where('renewingPolicies.0.id', $expiringLocalToday->id)
            ->where('renewingPolicies.1.id', $expiringAtLocalWindowEdge->id)
        );
})->with([
    // 00:30 on 16 Jan in Beirut: the UTC date's expiry has already passed locally.
    'east of UTC, just after local midnight' => ['2026-01-15 22:30:00', 'Asia/Beirut', '2026-01-16', '2026-02-15', '2026-01-15', '2026-02-16'],
    // 22:00 on 15 Jan in New York: the UTC window's last day is still a day out locally.
    'west of UTC, just before local midnight' => ['2026-01-16 03:00:00', 'America/New_York', '2026-01-15', '2026-02-14', '2026-01-14', '2026-02-15'],
]);

test('clientsCount counts the agent\'s distinct, non-deleted clients across live policies of any status', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->create();
    $repeatClient = Client::factory()->forOrganization($user)->create();
    $deletedClient = Client::factory()->forOrganization($user)->create();

    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'agent_id' => $agent->id, 'client_id' => $repeatClient->id]);
    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'agent_id' => $agent->id, 'client_id' => $repeatClient->id, 'status' => PolicyStatus::Frozen->value]);
    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'agent_id' => $agent->id, 'client_id' => Client::factory()->forOrganization($user), 'status' => PolicyStatus::Cancelled->value]);
    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'agent_id' => $agent->id, 'client_id' => Client::factory()->forOrganization($user)])->delete();
    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'agent_id' => $agent->id, 'client_id' => $deletedClient->id]);
    $deletedClient->delete();
    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'agent_id' => Agent::factory()->forOrganization($user), 'client_id' => Client::factory()->forOrganization($user)]);

    $this->actingAs($user)
        ->get(route('agents.show', $agent))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('clientsCount', 2)
            ->where('policiesCount', 4)
        );
});
