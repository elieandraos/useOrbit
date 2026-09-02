<?php

declare(strict_types=1);

use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Models\Country;
use App\Models\Organization;
use App\Models\User;
use App\Support\Tenancy\OrganizationContext;
use LogicException;

test('country resolves the user\'s associated country', function () {
    $country = Country::query()->create([
        'name' => 'Lebanon',
        'iso2' => 'LB',
        'iso3' => 'LBN',
    ]);
    $user = User::factory()->create(['country_id' => $country->id]);

    expect($user->country)->toBeInstanceOf(Country::class)
        ->and($user->country->is($country))->toBeTrue();
});

test('organization resolves the user\'s organization', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->forOrganization($organization)->create();

    expect($user->organization)->toBeInstanceOf(Organization::class)
        ->and($user->organization->is($organization))->toBeTrue();
});

test('inviter resolves the user who sent the invitation', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization)->create();
    $invitee = User::factory()->forOrganization($organization)->create(['invited_by' => $owner->id]);

    expect($invitee->inviter?->is($owner))->toBeTrue();
});

test('inviter is null once the inviting user has been deleted', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization)->create();
    $invitee = User::factory()->forOrganization($organization)->create(['invited_by' => $owner->id]);

    $owner->delete();

    expect($invitee->fresh()->inviter)->toBeNull();
});

test('activeInCurrentOrganization returns an active user scoped to the current organization', function () {
    $organization = Organization::factory()->create();
    $activeMember = User::factory()->forOrganization($organization)->create();
    app(OrganizationContext::class)->set($organization->id);

    $recipients = User::query()->activeInCurrentOrganization()->get();

    expect($recipients)->toHaveCount(1)
        ->and($recipients->first()->id)->toBe($activeMember->id);
});

test('activeInCurrentOrganization excludes an active user from another organization', function () {
    $organization = Organization::factory()->create();
    User::factory()->withOrganization()->create();
    app(OrganizationContext::class)->set($organization->id);

    $recipients = User::query()->activeInCurrentOrganization()->get();

    expect($recipients)->toBeEmpty();
});

test('activeInCurrentOrganization excludes an invited user in the current organization', function () {
    $organization = Organization::factory()->create();
    User::factory()->forOrganization($organization)->create(['status' => OrganizationMemberStatus::Invited]);
    app(OrganizationContext::class)->set($organization->id);

    $recipients = User::query()->activeInCurrentOrganization()->get();

    expect($recipients)->toBeEmpty();
});

test('activeInCurrentOrganization excludes a suspended user in the current organization', function () {
    $organization = Organization::factory()->create();
    User::factory()->forOrganization($organization)->create(['status' => OrganizationMemberStatus::Suspended]);
    app(OrganizationContext::class)->set($organization->id);

    $recipients = User::query()->activeInCurrentOrganization()->get();

    expect($recipients)->toBeEmpty();
});

test('activeInCurrentOrganization fails closed when no organization context has been established', function () {
    User::factory()->withOrganization()->create();

    User::query()->activeInCurrentOrganization()->get();
})->throws(LogicException::class, 'No organization context has been established.');

test('query without the scope remains unscoped across organizations', function () {
    User::factory()->withOrganization()->create();
    User::factory()->withOrganization()->create();

    expect(User::query()->count())->toBe(2);
});

test('privileged includes an owner', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    $recipients = User::query()->privileged()->get();

    expect($recipients->pluck('id')->all())->toBe([$owner->id]);
});

test('privileged includes an admin', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->forOrganization($organization, OrganizationRole::Admin)->create();

    $recipients = User::query()->privileged()->get();

    expect($recipients->pluck('id')->all())->toBe([$admin->id]);
});

test('privileged excludes a plain member', function () {
    $organization = Organization::factory()->create();
    User::factory()->forOrganization($organization, OrganizationRole::Member)->create();

    $recipients = User::query()->privileged()->get();

    expect($recipients)->toBeEmpty();
});

test('privileged alone does not imply active membership, including an invited admin', function () {
    $organization = Organization::factory()->create();
    $invitedAdmin = User::factory()->forOrganization($organization, OrganizationRole::Admin)->create(['status' => OrganizationMemberStatus::Invited]);

    $recipients = User::query()->privileged()->get();

    expect($recipients->pluck('id')->all())->toBe([$invitedAdmin->id]);
});

test('privileged alone does not imply active membership, including a suspended admin', function () {
    $organization = Organization::factory()->create();
    $suspendedAdmin = User::factory()->forOrganization($organization, OrganizationRole::Admin)->create(['status' => OrganizationMemberStatus::Suspended]);

    $recipients = User::query()->privileged()->get();

    expect($recipients->pluck('id')->all())->toBe([$suspendedAdmin->id]);
});

test('privileged alone is not scoped to the current organization', function () {
    $ownOrgAdmin = User::factory()->withOrganization()->create(['role' => OrganizationRole::Admin]);
    $otherOrgAdmin = User::factory()->withOrganization()->create(['role' => OrganizationRole::Admin]);

    $recipients = User::query()->privileged()->get();

    expect($recipients->pluck('id')->sort()->values()->all())
        ->toBe(collect([$ownOrgAdmin->id, $otherOrgAdmin->id])->sort()->values()->all());
});

test('activeInCurrentOrganization combined with privileged returns only active owners and admins in the current organization', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $admin = User::factory()->forOrganization($organization, OrganizationRole::Admin)->create();
    User::factory()->forOrganization($organization, OrganizationRole::Member)->create();
    User::factory()->forOrganization($organization, OrganizationRole::Admin)->create(['status' => OrganizationMemberStatus::Invited]);
    User::factory()->forOrganization($organization, OrganizationRole::Admin)->create(['status' => OrganizationMemberStatus::Suspended]);
    User::factory()->withOrganization()->create(['role' => OrganizationRole::Owner]);
    app(OrganizationContext::class)->set($organization->id);

    $recipients = User::query()->activeInCurrentOrganization()->privileged()->get();

    expect($recipients->pluck('id')->sort()->values()->all())
        ->toBe(collect([$owner->id, $admin->id])->sort()->values()->all());
});

test('deletes expired pending invited users', function () {
    $organization = Organization::factory()->create();
    $invitee = User::factory()->forOrganization($organization)->create([
        'password' => null,
        'status' => OrganizationMemberStatus::Invited,
        'invitation_token' => hash('sha256', 'some-token'),
        'invitation_expires_at' => now()->subDay(),
    ]);

    $this->artisan('model:prune', ['--model' => [User::class]])->assertSuccessful();

    expect(User::query()->whereKey($invitee->id)->exists())->toBeFalse();
});

test('removes the pruned invitee from the organization roster', function () {
    $organization = Organization::factory()->create();
    User::factory()->forOrganization($organization)->create([
        'password' => null,
        'status' => OrganizationMemberStatus::Invited,
        'invitation_token' => hash('sha256', 'some-token'),
        'invitation_expires_at' => now()->subDay(),
    ]);

    $this->artisan('model:prune', ['--model' => [User::class]])->assertSuccessful();

    expect($organization->fresh()->users()->count())->toBe(0);
});

test('leaves non-expired pending invited users untouched', function () {
    $organization = Organization::factory()->create();
    $invitee = User::factory()->forOrganization($organization)->create([
        'password' => null,
        'status' => OrganizationMemberStatus::Invited,
        'invitation_token' => hash('sha256', 'some-token'),
        'invitation_expires_at' => now()->addDays(7),
    ]);

    $this->artisan('model:prune', ['--model' => [User::class]])->assertSuccessful();

    expect(User::query()->whereKey($invitee->id)->exists())->toBeTrue();
});

test('leaves active members untouched regardless of a past invitation expiry', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->forOrganization($organization)->create([
        'invitation_expires_at' => now()->subDay(),
    ]);

    $this->artisan('model:prune', ['--model' => [User::class]])->assertSuccessful();

    expect(User::query()->whereKey($member->id)->exists())->toBeTrue();
});

test('leaves users who accepted their invitation untouched', function () {
    $organization = Organization::factory()->create();
    $invitee = User::factory()->forOrganization($organization)->create([
        'status' => OrganizationMemberStatus::Invited,
        'invitation_token' => hash('sha256', 'some-token'),
        'invitation_expires_at' => now()->subDay(),
    ]);

    $this->artisan('model:prune', ['--model' => [User::class]])->assertSuccessful();

    expect(User::query()->whereKey($invitee->id)->exists())->toBeTrue();
});

test('prunable results span organizations, unaffected by the acting user\'s tenant scope', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    $acting = User::factory()->forOrganization($orgA)->create();
    $expiredInOrgA = User::factory()->forOrganization($orgA)->create([
        'password' => null,
        'status' => OrganizationMemberStatus::Invited,
        'invitation_token' => hash('sha256', 'org-a-token'),
        'invitation_expires_at' => now()->subDay(),
    ]);
    $expiredInOrgB = User::factory()->forOrganization($orgB)->create([
        'password' => null,
        'status' => OrganizationMemberStatus::Invited,
        'invitation_token' => hash('sha256', 'org-b-token'),
        'invitation_expires_at' => now()->subDay(),
    ]);
    $this->actingAs($acting);

    $prunableIds = (new User)->prunable()->pluck('id');

    expect($prunableIds)->toContain($expiredInOrgA->id, $expiredInOrgB->id);
});
