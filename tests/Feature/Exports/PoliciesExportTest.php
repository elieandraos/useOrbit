<?php

declare(strict_types=1);

use App\Enums\PolicyClass;
use App\Enums\PolicySource;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use App\Exports\PoliciesExport;
use App\Models\Agent;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Policy;
use App\Models\User;

test('headings returns the export column labels', function () {
    $export = new PoliciesExport([], null, 'asc');

    expect($export->headings())->toBe([
        'Policy Number',
        'Class',
        'Type',
        'Client',
        'Carrier',
        'Agent',
        'Effective Date',
        'Expiry Date',
        'Premium Amount',
        'Discount Amount',
        'Status',
        'Source',
    ]);
});

test('map transforms a policy into an export row', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);

    /** @var Client $client */
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id, 'first_name' => 'Aline', 'last_name' => 'Haddad']);
    /** @var Carrier $carrier */
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id, 'name' => 'Bankers Assurance']);
    /** @var Agent $agent */
    $agent = Agent::factory()->forOrganization($user)->create(['created_by' => $user->id, 'first_name' => 'Karim', 'last_name' => 'Aoun']);

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->create([
        'created_by' => $user->id,
        'policy_number' => 'POL-1000',
        'class' => PolicyClass::Fire,
        'type' => PolicyType::Single,
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
        'agent_id' => $agent->id,
        'effective_date' => '2024-01-10',
        'expiry_date' => '2025-01-10',
        'premium_amount' => 1000,
        'discount_amount' => 150,
        'status' => PolicyStatus::Active,
        'source' => PolicySource::Agent,
    ])->load(['client', 'carrier', 'agent']);

    $export = new PoliciesExport([], null, 'asc');

    expect($export->map($policy))->toBe([
        'POL-1000',
        'Fire',
        'Single',
        'Aline Haddad',
        'Bankers Assurance',
        'Karim Aoun',
        '2024-01-10',
        '2025-01-10',
        '1000.00',
        '150.00',
        'Active',
        'Agent',
    ]);
});

test('map returns a blank agent when the policy has none', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);

    /** @var Client $client */
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    /** @var Carrier $carrier */
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->create([
        'created_by' => $user->id,
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
        'agent_id' => null,
    ])->load(['client', 'carrier', 'agent']);

    $export = new PoliciesExport([], null, 'asc');

    expect($export->map($policy)[5])->toBeNull();
});
