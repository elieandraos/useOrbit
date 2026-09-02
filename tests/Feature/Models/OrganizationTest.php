<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\QueryException;

test('users resolves every user belonging to the organization', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->forOrganization($organization)->create();

    expect($organization->users()->get()->pluck('id'))->toContain($member->id);
});

test('owner resolves the user with the owner role', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    User::factory()->forOrganization($organization)->create();

    expect($organization->owner()?->is($owner))->toBeTrue();
});

test('deleting an organization that still has users fails', function () {
    $organization = Organization::factory()->create();
    User::factory()->forOrganization($organization)->create();

    $organization->delete();
})->throws(QueryException::class);
