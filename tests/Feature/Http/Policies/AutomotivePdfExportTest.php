<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $policy = Policy::factory()->automotive()->create();

    $this->get(route('policies.automotive.export-pdf', $policy))
        ->assertRedirect(route('login'));
});

test('authenticated user can export an automotive policy from their organization to pdf', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->automotive()->create([
        'created_by' => $user->id,
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
    ]);

    $response = $this->actingAs($user)
        ->get(route('policies.automotive.export-pdf', $policy))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($response->headers->get('content-disposition'))
        ->toContain("$policy->slug.pdf");
});

test('authenticated user gets 404 for a policy from another organization', function () {
    $user = User::factory()->withOrganization()->create();

    $otherOrganization = Organization::factory()->create();
    $policy = Policy::factory()->automotive()->create(['organization_id' => $otherOrganization->id]);

    $this->actingAs($user)
        ->get(route('policies.automotive.export-pdf', $policy))
        ->assertNotFound();
});

test('authenticated user gets 404 for a non-automotive policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('policies.automotive.export-pdf', $policy))
        ->assertNotFound();
});
