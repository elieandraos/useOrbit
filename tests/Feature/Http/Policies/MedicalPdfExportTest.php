<?php

declare(strict_types=1);

use App\Enums\PolicyStatus;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\PolicyInsured;
use App\Models\User;
use App\Support\Policies\PolicyDisplayStatusResolver;

test('guests are redirected to the login page', function () {
    $policy = Policy::factory()->medical()->create();

    $this->get(route('policies.medical.export-pdf', $policy))
        ->assertRedirect(route('login'));
});

test('authenticated user can export a single medical policy from their organization to pdf', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->medical()->create([
        'created_by' => $user->id,
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
        'type' => 'single',
    ]);

    $response = $this->actingAs($user)
        ->get(route('policies.medical.export-pdf', $policy))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($response->headers->get('content-disposition'))
        ->toContain("$policy->slug.pdf");
});

test('authenticated user can export a group medical policy with covered members to pdf', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->medical()->create([
        'created_by' => $user->id,
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
        'type' => 'group',
    ]);
    PolicyInsured::factory()->for($policy)->create();

    $response = $this->actingAs($user)
        ->get(route('policies.medical.export-pdf', $policy))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($response->headers->get('content-disposition'))
        ->toContain("$policy->slug.pdf");
});

test('authenticated user gets 404 for a policy from another organization', function () {
    $user = User::factory()->withOrganization()->create();

    $otherOrganization = Organization::factory()->create();
    $policy = Policy::factory()->medical()->create(['organization_id' => $otherOrganization->id]);

    $this->actingAs($user)
        ->get(route('policies.medical.export-pdf', $policy))
        ->assertNotFound();
});

test('authenticated user gets 404 for a non-medical policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->automotive()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('policies.medical.export-pdf', $policy))
        ->assertNotFound();
});

test('the pdf shows every amount with the policy currency code and two decimals', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->medical()->lbp()->create([
        'created_by' => $user->id,
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
        'premium_amount' => 150000,
        'discount_amount' => 2500,
    ]);

    $html = view('exports.policy-medical-profile', [
        'policy' => $policy,
        'organization' => $user->organization,
        'displayStatus' => app(PolicyDisplayStatusResolver::class)->for($policy),
    ])->render();

    expect($html)->toContain('LBP 150,000.00', 'LBP 2,500.00');
});

test('the pdf shows the current name of the organization that owns the policy', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->medical()->create([
        'created_by' => $user->id,
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
    ]);
    $user->organization->update(['name' => 'Cedar Brokers']);

    $html = renderedPdfHtml(fn () => $this->actingAs($user)->get(route('policies.medical.export-pdf', $policy))->assertOk());

    expect($html)->toContain('Cedar Brokers');
});

test('the pdf shows the display status computed from the organization-local date', function (string $status, string $label) {
    $this->travelTo('2026-03-10 20:00:00');
    $user = User::factory()->withOrganization()->create();
    $user->organization->update(['timezone' => 'Asia/Tokyo']);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->medical()->create([
        'created_by' => $user->id,
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
        'status' => $status,
        'effective_date' => '2025-03-11',
        'expiry_date' => '2026-03-10',
    ]);

    $html = renderedPdfHtml(fn () => $this->actingAs($user)->get(route('policies.medical.export-pdf', $policy))->assertOk());

    expect($html)->toContain("<span class=\"value\">$label</span>")
        ->not->toContain('<span class="value">Active</span>');
})->with([
    'expired in the organization timezone' => [PolicyStatus::Active->value, 'Expired'],
    'frozen' => [PolicyStatus::Frozen->value, 'Frozen'],
]);
