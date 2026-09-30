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

test('a filter query param narrows the response to matching policies', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Policy $match */
    $match = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'policy_number' => 'POL-1000']);
    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'policy_number' => 'POL-2000']);

    $this->actingAs($user)
        ->get(route('policies.index', ['search' => '1000']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('policies.data', 1)
            ->where('policies.data.0.id', $match->id)
        );
});

test('no status is hidden by default, unlike the archived-by-default Client behavior', function () {
    $user = User::factory()->withOrganization()->create();

    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'status' => PolicyStatus::Active]);
    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'status' => PolicyStatus::Cancelled]);
    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'status' => PolicyStatus::Frozen]);

    $this->actingAs($user)
        ->get(route('policies.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('policies.data', 3));
});

test('a status filter narrows the response to the exact matching status only', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Policy $match */
    $match = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'status' => PolicyStatus::Frozen]);
    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'status' => PolicyStatus::Active]);

    $this->actingAs($user)
        ->get(route('policies.index', ['status' => PolicyStatus::Frozen->value]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('policies.data', 1)
            ->where('policies.data.0.id', $match->id)
        );
});

test('a class[] filter narrows the response to any of the selected classes', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Policy $fire */
    $fire = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'class' => PolicyClass::Fire]);
    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'class' => PolicyClass::Travel]);

    $this->actingAs($user)
        ->get(route('policies.index', ['class' => [PolicyClass::Fire->value]]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('policies.data', 1)
            ->where('policies.data.0.id', $fire->id)
        );
});

test('an invalid status is rejected', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.index', ['status' => 'unknown']))
        ->assertInvalid(['status']);
});

test('an invalid type is rejected', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.index', ['type' => 'unknown']))
        ->assertInvalid(['type']);
});

test('an invalid class entry is rejected', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.index', ['class' => ['unknown']]))
        ->assertInvalid(['class.0']);
});

test('a carrier_id from another organization is rejected', function () {
    $user = User::factory()->withOrganization()->create();

    $otherOrganization = Organization::factory()->create();
    $otherCarrier = Carrier::factory()->create(['organization_id' => $otherOrganization->id]);

    $this->actingAs($user)
        ->get(route('policies.index', ['carrier_id' => $otherCarrier->id]))
        ->assertInvalid(['carrier_id']);
});

test('an invalid source is rejected', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.index', ['source' => 'unknown']))
        ->assertInvalid(['source']);
});

test('effective_to before effective_from is rejected', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.index', ['effective_from' => '2024-01-10', 'effective_to' => '2024-01-01']))
        ->assertInvalid(['effective_to']);
});

test('non-numeric amount bounds are rejected', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.index', ['amount_min' => 'many']))
        ->assertInvalid(['amount_min']);
});

test('amount_max below amount_min is rejected', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.index', ['amount_min' => 500, 'amount_max' => 100]))
        ->assertInvalid(['amount_max']);
});

test('the page exposes the filter option lists used by the filters drawer', function () {
    $user = User::factory()->withOrganization()->create();
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id, 'name' => 'Bankers Assurance']);

    $this->actingAs($user)
        ->get(route('policies.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('statuses', 3)
            ->has('types', 2)
            ->has('classes', 6)
            ->has('sources', 4)
            ->has('carriers', 1)
            ->where('carriers.0.id', $carrier->id)
            ->where('carriers.0.name', 'Bankers Assurance')
        );
});

test('the page does not expose carriers from another organization', function () {
    $user = User::factory()->withOrganization()->create();

    $otherOrganization = Organization::factory()->create();
    Carrier::factory()->create(['organization_id' => $otherOrganization->id]);

    $this->actingAs($user)
        ->get(route('policies.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('carriers', 0));
});

test('the filters prop reflects no applied filters by default', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('filters.search', null)
            ->where('filters.status', null)
            ->where('filters.type', null)
            ->where('filters.class', null)
            ->where('filters.carrier_id', null)
            ->where('filters.source', null)
            ->where('filters.effective_from', null)
            ->where('filters.effective_to', null)
            ->where('filters.amount_min', null)
            ->where('filters.amount_max', null)
        );
});

test('the filters prop mirrors the applied query params', function () {
    $user = User::factory()->withOrganization()->create();
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('policies.index', [
            'search' => 'POL-1000',
            'status' => PolicyStatus::Frozen->value,
            'type' => PolicyType::Group->value,
            'class' => [PolicyClass::Fire->value, PolicyClass::Life->value],
            'carrier_id' => $carrier->id,
            'source' => PolicySource::Agent->value,
            'effective_from' => '2024-01-01',
            'effective_to' => '2024-12-31',
            'amount_min' => 100,
            'amount_max' => 5000,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('filters.search', 'POL-1000')
            ->where('filters.status', PolicyStatus::Frozen->value)
            ->where('filters.type', PolicyType::Group->value)
            ->where('filters.class', [PolicyClass::Fire->value, PolicyClass::Life->value])
            ->where('filters.carrier_id', (string) $carrier->id)
            ->where('filters.source', PolicySource::Agent->value)
            ->where('filters.effective_from', '2024-01-01')
            ->where('filters.effective_to', '2024-12-31')
            ->where('filters.amount_min', '100')
            ->where('filters.amount_max', '5000')
        );
});
