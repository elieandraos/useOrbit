<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Database\QueryException;

test('organization resolves the pivot row\'s organization', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var OrganizationMember $membership */
    $membership = OrganizationMember::query()->where('user_id', $user->id)->firstOrFail();

    expect($membership->organization)->toBeInstanceOf(Organization::class)
        ->and($membership->organization->id)->toBe($user->current_organization_id);
});

test('inviter resolves the user who sent the invitation', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization)->create();
    $invitee = User::factory()->create();
    $invitee->organizations()->attach($organization, [
        'role' => 'member',
        'status' => 'invited',
        'invited_by' => $owner->id,
    ]);

    /** @var OrganizationMember $membership */
    $membership = OrganizationMember::query()->where('user_id', $invitee->id)->firstOrFail();

    expect($membership->inviter?->is($owner))->toBeTrue();
});

test('inviter is null once the inviting user has been deleted', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization)->create();
    $invitee = User::factory()->create();
    $invitee->organizations()->attach($organization, [
        'role' => 'member',
        'status' => 'invited',
        'invited_by' => $owner->id,
    ]);

    $owner->delete();

    /** @var OrganizationMember $membership */
    $membership = OrganizationMember::query()->where('user_id', $invitee->id)->firstOrFail();

    expect($membership->inviter)->toBeNull();
});

test('a user cannot belong to more than one organization at the database level', function () {
    $user = User::factory()->withOrganization()->create();
    $secondOrganization = Organization::factory()->create();

    $user->organizations()->attach($secondOrganization, [
        'role' => 'member',
        'status' => 'active',
    ]);
})->throws(QueryException::class);
