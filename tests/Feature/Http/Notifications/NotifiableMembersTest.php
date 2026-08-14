<?php

declare(strict_types=1);

use App\Enums\OrganizationMemberStatus;
use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('notify.recipients'))
        ->assertRedirect(route('login'));
});

test('returns active same-org users, excluding the requesting actor', function () {
    $organization = Organization::factory()->create();
    $actor = User::factory()->forOrganization($organization)->create();
    $colleague = User::factory()->forOrganization($organization)->create(['name' => 'Jane Doe', 'email' => 'jane.doe@useorbit.com']);
    User::factory()->forOrganization($organization)->create(['status' => OrganizationMemberStatus::Invited]);
    User::factory()->forOrganization($organization)->create(['status' => OrganizationMemberStatus::Suspended]);
    User::factory()->withOrganization()->create();

    $response = $this->actingAs($actor)
        ->getJson(route('notify.recipients'))
        ->assertOk();

    expect($response->json('data'))->toBe([
        ['id' => $colleague->id, 'name' => 'Jane Doe', 'email' => 'jane.doe@useorbit.com'],
    ]);
});
