<?php

declare(strict_types=1);

use App\Enums\ClientType;
use App\Enums\PolicyClass;
use App\Enums\PolicySource;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use App\Http\Resources\PolicyResource;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('policies.index'))
        ->assertRedirect(route('login'));
});

test('authenticated user can list their organization policies', function () {
    $user = User::factory()->withOrganization()->create();
    Policy::factory(2)->forOrganization($user)->create(['created_by' => $user->id]);

    $this->assertDatabaseCount('policies', 2);

    $this->actingAs($user)
        ->get(route('policies.index'))
        ->assertOk()
        ->assertHasPaginatedResource(
            'policies',
            PolicyResource::collection(
                Policy::query()->with(['client', 'carrier'])->latest('effective_date')->orderBy('id')->paginate(7)
            )
        );
});

test('policies from another organization are not included', function () {
    $user = User::factory()->withOrganization()->create();
    Policy::factory(2)->forOrganization($user)->create(['created_by' => $user->id]);

    $otherOrganization = Organization::factory()->create();
    Policy::factory(3)->create(['organization_id' => $otherOrganization->id]);

    $this->assertDatabaseCount('policies', 5);

    $this->actingAs($user)
        ->get(route('policies.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('policies.data', 2));
});

test('the endpoint returns a valid paginated response when the organization has zero policies', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('policies.data', 0)
            ->has('policies.meta')
            ->has('policies.links')
        );
});

test('the policy list exposes computed and labeled fields', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create([
        'created_by' => $user->id,
        'client_type' => ClientType::Company,
        'company_name' => 'Acme Corp',
    ]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id, 'name' => 'Bankers Assurance']);

    Policy::factory()->forOrganization($user)->create([
        'created_by' => $user->id,
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
        'class' => PolicyClass::Automotive,
        'type' => PolicyType::Group,
        'status' => PolicyStatus::Frozen,
        'source' => PolicySource::Friend,
        'premium_amount' => 1000,
        'discount_amount' => 150,
    ]);

    $this->actingAs($user)
        ->get(route('policies.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('policies.data.0.client.full_name', 'Acme Corp')
            ->where('policies.data.0.carrier.name', 'Bankers Assurance')
            ->where('policies.data.0.premium_amount', '1000.00')
            ->where('policies.data.0.discount_amount', '150.00')
            ->where('policies.data.0.net_premium', '850.00')
            ->where('policies.data.0.class_label', 'Automotive')
            ->where('policies.data.0.type_label', 'Group')
            ->where('policies.data.0.status_label', 'Frozen')
            ->where('policies.data.0.source_label', 'Friend')
        );
});

test('the policy list computes an individual client\'s full name from first and last name', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create([
        'created_by' => $user->id,
        'client_type' => ClientType::Individual,
        'first_name' => 'Amelia',
        'last_name' => 'Hartwell',
    ]);

    Policy::factory()->forOrganization($user)->create([
        'created_by' => $user->id,
        'client_id' => $client->id,
    ]);

    $this->actingAs($user)
        ->get(route('policies.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('policies.data.0.client.full_name', 'Amelia Hartwell')
        );
});
