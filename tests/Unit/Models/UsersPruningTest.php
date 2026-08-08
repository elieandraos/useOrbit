<?php

declare(strict_types=1);

use App\Enums\OrganizationMemberStatus;
use App\Models\Organization;
use App\Models\User;

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
