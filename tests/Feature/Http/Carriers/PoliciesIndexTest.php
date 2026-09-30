<?php

declare(strict_types=1);

use App\Http\Resources\PolicyResource;
use App\Models\Carrier;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $carrier = Carrier::factory()->create();

    $this->get(route('carriers.policies.index', $carrier))
        ->assertRedirect(route('login'));
});

test('authenticated user can list a carrier policies', function () {
    $user = User::factory()->withOrganization()->create();
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    Policy::factory(2)->forOrganization($user)->create(['created_by' => $user->id, 'carrier_id' => $carrier->id]);

    $this->actingAs($user)
        ->get(route('carriers.policies.index', $carrier))
        ->assertOk()
        ->assertHasPaginatedResource(
            'policies',
            PolicyResource::collection(
                $carrier->policies()->with(['client', 'carrier'])->latest('effective_date')->orderBy('id')->paginate(7)
            )
        );
});

test('policies from another carrier are not included', function () {
    $user = User::factory()->withOrganization()->create();
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $otherCarrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    Policy::factory(2)->forOrganization($user)->create(['created_by' => $user->id, 'carrier_id' => $carrier->id]);
    Policy::factory(3)->forOrganization($user)->create(['created_by' => $user->id, 'carrier_id' => $otherCarrier->id]);

    $this->actingAs($user)
        ->get(route('carriers.policies.index', $carrier))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('policies.data', 2));
});

test('authenticated user gets 404 for a carrier from another organization', function () {
    $user = User::factory()->withOrganization()->create();

    $otherOrganization = Organization::factory()->create();
    $carrier = Carrier::factory()->for($otherOrganization)->create();

    $this->actingAs($user)
        ->get(route('carriers.policies.index', $carrier))
        ->assertNotFound();
});

test('the endpoint returns a valid paginated response when the carrier has zero policies', function () {
    $user = User::factory()->withOrganization()->create();
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('carriers.policies.index', $carrier))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('policies.data', 0)
            ->has('policies.meta')
            ->has('policies.links')
            ->where('policiesCount', 0)
        );
});
