<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Policy;
use App\Models\Scopes\CurrentOrganizationScope;
use App\Sorts\PolicySort;

test('policyNumber sorts alphabetically', function () {
    /** @var Policy $bravo */
    $bravo = Policy::factory()->create(['policy_number' => 'POL-B']);
    /** @var Policy $alpha */
    $alpha = Policy::factory()->create(['policy_number' => 'POL-A']);

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->sort(new PolicySort('policy_number', 'asc'))->get();

    expect($policies->pluck('id')->all())->toBe([$alpha->id, $bravo->id]);
});

test('policyNumber sort direction can be reversed', function () {
    /** @var Policy $bravo */
    $bravo = Policy::factory()->create(['policy_number' => 'POL-B']);
    /** @var Policy $alpha */
    $alpha = Policy::factory()->create(['policy_number' => 'POL-A']);

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->sort(new PolicySort('policy_number', 'desc'))->get();

    expect($policies->pluck('id')->all())->toBe([$bravo->id, $alpha->id]);
});

test('client sorts by the client display name', function () {
    /** @var Client $bravo */
    $bravo = Client::factory()->create(['first_name' => 'Bravo', 'last_name' => 'Zulu']);
    /** @var Client $alpha */
    $alpha = Client::factory()->create(['first_name' => 'Alpha', 'last_name' => 'Zulu']);

    /** @var Policy $policyForBravo */
    $policyForBravo = Policy::factory()->create(['client_id' => $bravo->id]);
    /** @var Policy $policyForAlpha */
    $policyForAlpha = Policy::factory()->create(['client_id' => $alpha->id]);

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->sort(new PolicySort('client', 'asc'))->get();

    expect($policies->pluck('id')->all())->toBe([$policyForAlpha->id, $policyForBravo->id]);
});

test('client sorts a mixed individual/company set by the displayed name', function () {
    /** @var Client $acme */
    $acme = Client::factory()->company()->create(['company_name' => 'Acme Logistics']);
    /** @var Client $bravo */
    $bravo = Client::factory()->create(['first_name' => 'Bravo', 'last_name' => 'Zulu']);
    /** @var Client $zenith */
    $zenith = Client::factory()->company()->create(['company_name' => 'Zenith Traders']);

    /** @var Policy $policyForAcme */
    $policyForAcme = Policy::factory()->create(['client_id' => $acme->id]);
    /** @var Policy $policyForBravo */
    $policyForBravo = Policy::factory()->create(['client_id' => $bravo->id]);
    /** @var Policy $policyForZenith */
    $policyForZenith = Policy::factory()->create(['client_id' => $zenith->id]);

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->sort(new PolicySort('client', 'asc'))->get();

    expect($policies->pluck('id')->all())->toBe([$policyForAcme->id, $policyForBravo->id, $policyForZenith->id]);
});

test('client breaks a tied first name by last name', function () {
    /** @var Client $zuluFirstName */
    $zuluFirstName = Client::factory()->create(['first_name' => 'Robin', 'last_name' => 'Zulu']);
    /** @var Client $alphaLastName */
    $alphaLastName = Client::factory()->create(['first_name' => 'Robin', 'last_name' => 'Alpha']);

    /** @var Policy $policyForZuluLastName */
    $policyForZuluLastName = Policy::factory()->create(['client_id' => $zuluFirstName->id]);
    /** @var Policy $policyForAlphaLastName */
    $policyForAlphaLastName = Policy::factory()->create(['client_id' => $alphaLastName->id]);

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->sort(new PolicySort('client', 'asc'))->get();

    expect($policies->pluck('id')->all())->toBe([$policyForAlphaLastName->id, $policyForZuluLastName->id]);
});

test('client sort direction can be reversed', function () {
    /** @var Client $bravo */
    $bravo = Client::factory()->create(['first_name' => 'Bravo', 'last_name' => 'Zulu']);
    /** @var Client $alpha */
    $alpha = Client::factory()->create(['first_name' => 'Alpha', 'last_name' => 'Zulu']);

    /** @var Policy $policyForBravo */
    $policyForBravo = Policy::factory()->create(['client_id' => $bravo->id]);
    /** @var Policy $policyForAlpha */
    $policyForAlpha = Policy::factory()->create(['client_id' => $alpha->id]);

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->sort(new PolicySort('client', 'desc'))->get();

    expect($policies->pluck('id')->all())->toBe([$policyForBravo->id, $policyForAlpha->id]);
});

test('effectiveDate sorts chronologically', function () {
    /** @var Policy $newest */
    $newest = Policy::factory()->create(['effective_date' => '2024-06-01']);
    /** @var Policy $oldest */
    $oldest = Policy::factory()->create(['effective_date' => '2023-01-01']);

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->sort(new PolicySort('effective_date', 'asc'))->get();

    expect($policies->pluck('id')->all())->toBe([$oldest->id, $newest->id]);
});

test('amount sorts by the premium amount', function () {
    /** @var Policy $expensive */
    $expensive = Policy::factory()->create(['premium_amount' => 5000]);
    /** @var Policy $cheap */
    $cheap = Policy::factory()->create(['premium_amount' => 500]);

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->sort(new PolicySort('amount', 'asc'))->get();

    expect($policies->pluck('id')->all())->toBe([$cheap->id, $expensive->id]);
});

test('amount sort direction can be reversed', function () {
    /** @var Policy $expensive */
    $expensive = Policy::factory()->create(['premium_amount' => 5000]);
    /** @var Policy $cheap */
    $cheap = Policy::factory()->create(['premium_amount' => 500]);

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->sort(new PolicySort('amount', 'desc'))->get();

    expect($policies->pluck('id')->all())->toBe([$expensive->id, $cheap->id]);
});

test('status sorts alphabetically', function () {
    /** @var Policy $frozen */
    $frozen = Policy::factory()->create(['status' => 'frozen']);
    /** @var Policy $active */
    $active = Policy::factory()->create(['status' => 'active']);

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->sort(new PolicySort('status', 'asc'))->get();

    expect($policies->pluck('id')->all())->toBe([$active->id, $frozen->id]);
});

test('default falls back to effective date, newest first', function () {
    /** @var Policy $newest */
    $newest = Policy::factory()->create(['effective_date' => '2024-06-01']);
    /** @var Policy $oldest */
    $oldest = Policy::factory()->create(['effective_date' => '2023-01-01']);

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->sort(new PolicySort(null, 'asc'))->get();

    expect($policies->pluck('id')->all())->toBe([$newest->id, $oldest->id]);
});

test('an unrecognized column falls back to the default', function () {
    /** @var Policy $newest */
    $newest = Policy::factory()->create(['effective_date' => '2024-06-01']);
    /** @var Policy $oldest */
    $oldest = Policy::factory()->create(['effective_date' => '2023-01-01']);

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->sort(new PolicySort('unknown', 'asc'))->get();

    expect($policies->pluck('id')->all())->toBe([$newest->id, $oldest->id]);
});

test('ties on every sortable column are broken by a stable primary key order', function (?string $column) {
    /** @var Client $client */
    $client = Client::factory()->create(['first_name' => 'Robin', 'last_name' => 'Haddad']);

    /** @var Policy $first */
    $first = Policy::factory()->create(['policy_number' => 'POL-0001', 'client_id' => $client->id, 'effective_date' => '2024-01-01', 'premium_amount' => 1000, 'status' => 'active']);
    /** @var Policy $second */
    $second = Policy::factory()->create(['policy_number' => 'POL-0001', 'client_id' => $client->id, 'effective_date' => '2024-01-01', 'premium_amount' => 1000, 'status' => 'active']);
    /** @var Policy $third */
    $third = Policy::factory()->create(['policy_number' => 'POL-0001', 'client_id' => $client->id, 'effective_date' => '2024-01-01', 'premium_amount' => 1000, 'status' => 'active']);

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->sort(new PolicySort($column, 'asc'))->get();

    expect($policies->pluck('id')->all())->toBe([$first->id, $second->id, $third->id]);
})->with([
    'policy_number' => 'policy_number',
    'client' => 'client',
    'effective_date' => 'effective_date',
    'amount' => 'amount',
    'status' => 'status',
    'default' => null,
]);
