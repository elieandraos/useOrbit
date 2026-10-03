<?php

declare(strict_types=1);

use App\Enums\PolicyClass;
use App\Enums\PolicySource;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use App\Exports\PoliciesExport;
use App\Models\Agent;
use App\Models\Carrier;
use App\Models\CarrierBranch;
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
        'Carrier Branch',
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
        'carrier_branch_id' => CarrierBranch::factory()->forCarrier($carrier)->create(['city' => 'Beirut', 'street' => 'Hamra Street'])->id,
        'agent_id' => $agent->id,
        'effective_date' => '2024-01-10',
        'expiry_date' => '2025-01-10',
        'premium_amount' => '9999999999999.99',
        'discount_amount' => 150,
        'status' => PolicyStatus::Active,
        'source' => PolicySource::Agent,
    ])->load(['client', 'carrier', 'carrierBranch', 'agent', 'currency']);

    $export = new PoliciesExport([]);

    expect($export->map($policy))->toBe([
        'POL-1000',
        'Fire',
        'Single',
        'Aline Haddad',
        'Bankers Assurance',
        'Beirut — Hamra Street',
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

test('map returns a blank agent and carrier branch when the policy has neither', function () {
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
    ])->load(['client', 'carrier', 'carrierBranch', 'agent', 'currency']);

    $export = new PoliciesExport([]);

    expect($export->map($policy)[5])->toBeNull()
        ->and($export->map($policy)[6])->toBeNull();
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
        'K' => '#,##0.00',
        'L' => '#,##0.00',
    ]);
});

test('query eager loads each policy currency and carrier branch so mapping rows adds no queries', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $parties = [
        'created_by' => $user->id,
        'client_id' => Client::factory()->forOrganization($user)->create(['created_by' => $user->id])->id,
        'carrier_id' => Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id])->id,
    ];
    Policy::factory(2)->forOrganization($user)->withCarrierBranch()->create($parties);
    Policy::factory()->forOrganization($user)->lbp()->create($parties);

    $export = new PoliciesExport([]);
    $policies = $export->query()->get();

    DB::enableQueryLog();
    $rows = $policies->map(fn (Policy $policy): array => $export->map($policy));

    expect(DB::getQueryLog())->toBeEmpty()
        ->and($rows->pluck(9)->sort()->values()->all())->toBe(['LBP', 'USD', 'USD'])
        ->and($rows->pluck(5)->filter()->count())->toBe(2);
});
