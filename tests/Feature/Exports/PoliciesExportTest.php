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
use Illuminate\Support\Facades\DB;

test('headings returns the export column labels', function () {
    $export = new PoliciesExport([]);

    expect($export->headings())->toBe([
        'Policy Number',
        'Class',
        'Type',
        'Client',
        'Carrier',
        'Agent',
        'Effective Date',
        'Expiry Date',
        'Currency',
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
    $policy = Policy::factory()->forOrganization($user)->lbp()->create([
        'created_by' => $user->id,
        'policy_number' => 'POL-1000',
        'class' => PolicyClass::Fire,
        'type' => PolicyType::Single,
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
        'agent_id' => $agent->id,
        'effective_date' => '2024-01-10',
        'expiry_date' => '2025-01-10',
        'premium_amount' => '9999999999999.99',
        'discount_amount' => 150,
        'status' => PolicyStatus::Active,
        'source' => PolicySource::Agent,
    ])->load(['client', 'carrier', 'agent', 'currency']);

    $export = new PoliciesExport([]);

    expect($export->map($policy))->toBe([
        'POL-1000',
        'Fire',
        'Single',
        'Aline Haddad',
        'Bankers Assurance',
        'Karim Aoun',
        '2024-01-10',
        '2025-01-10',
        'LBP',
        9999999999999.99,
        150.0,
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
    ])->load(['client', 'carrier', 'agent', 'currency']);

    $export = new PoliciesExport([]);

    expect($export->map($policy)[5])->toBeNull();
});

test('query orders policies sharing an effective date the same way the index does', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);

    /** @var Policy $older */
    $older = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'effective_date' => '2024-01-01']);
    /** @var Policy $first */
    $first = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'effective_date' => '2024-06-01']);
    /** @var Policy $second */
    $second = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'effective_date' => '2024-06-01']);

    $export = new PoliciesExport([]);

    expect($export->query()->pluck('id')->all())->toBe([$first->id, $second->id, $older->id]);
});

test('amount columns are formatted as numbers with two decimals', function () {
    $export = new PoliciesExport([]);

    expect($export->columnFormats())->toBe([
        'J' => '#,##0.00',
        'K' => '#,##0.00',
    ]);
});

test('query eager loads each policy currency so mapping rows adds no queries', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $parties = [
        'created_by' => $user->id,
        'client_id' => Client::factory()->forOrganization($user)->create(['created_by' => $user->id])->id,
        'carrier_id' => Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id])->id,
    ];
    Policy::factory(2)->forOrganization($user)->create($parties);
    Policy::factory()->forOrganization($user)->lbp()->create($parties);

    $export = new PoliciesExport([]);
    $policies = $export->query()->get();

    DB::enableQueryLog();
    $currencyCodes = $policies->map(fn (Policy $policy): string => $export->map($policy)[8])->sort()->values()->all();

    expect(DB::getQueryLog())->toBeEmpty()
        ->and($currencyCodes)->toBe(['LBP', 'USD', 'USD']);
});
