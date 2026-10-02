<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $policy = Policy::factory()->life()->create();

    $this->get(route('policies.life.export-pdf', $policy))
        ->assertRedirect(route('login'));
});

test('authenticated user can export a life policy from their organization to pdf', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->life()->create([
        'created_by' => $user->id,
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
    ]);

    $response = $this->actingAs($user)
        ->get(route('policies.life.export-pdf', $policy))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($response->headers->get('content-disposition'))
        ->toContain("$policy->slug.pdf");
});

test('authenticated user gets 404 for a policy from another organization', function () {
    $user = User::factory()->withOrganization()->create();

    $otherOrganization = Organization::factory()->create();
    $policy = Policy::factory()->life()->create(['organization_id' => $otherOrganization->id]);

    $this->actingAs($user)
        ->get(route('policies.life.export-pdf', $policy))
        ->assertNotFound();
});

test('authenticated user gets 404 for a non-life policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('policies.life.export-pdf', $policy))
        ->assertNotFound();
});

test('the pdf shows every amount with the policy currency code and two decimals', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->life()->lbp()->create([
        'created_by' => $user->id,
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
        'premium_amount' => 150000,
        'discount_amount' => 2500,
    ]);
    $policy->lifeDetails->update(['sum_assured' => '9999999999999.99']);

    $html = view('exports.policy-life-profile', ['policy' => $policy, 'organization' => $user->organization])->render();

    expect($html)->toContain('LBP 150,000.00', 'LBP 2,500.00', 'LBP 9,999,999,999,999.99');
});

test('the pdf shows the current name of the organization that owns the policy', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->life()->create([
        'created_by' => $user->id,
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
    ]);
    $user->organization->update(['name' => 'Cedar Brokers']);

    $html = renderedPdfHtml(fn () => $this->actingAs($user)->get(route('policies.life.export-pdf', $policy))->assertOk());

    expect($html)->toContain('Cedar Brokers');
});
