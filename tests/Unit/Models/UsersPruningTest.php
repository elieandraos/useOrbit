<?php

declare(strict_types=1);

use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

test('deletes expired pending invited users', function () {
    $organization = Organization::factory()->create();
    $invitee = User::factory()->create(['password' => null]);
    $invitee->organizations()->attach($organization, [
        'role' => OrganizationRole::Member->value,
        'status' => OrganizationMemberStatus::Invited->value,
        'token' => hash('sha256', 'some-token'),
        'expires_at' => now()->subDay(),
    ]);

    $this->artisan('model:prune', ['--model' => [User::class]])->assertSuccessful();

    expect(User::query()->whereKey($invitee->id)->exists())->toBeFalse();
});

test('cascades the pivot deletion for a pruned invitee', function () {
    $organization = Organization::factory()->create();
    $invitee = User::factory()->create(['password' => null]);
    $invitee->organizations()->attach($organization, [
        'role' => OrganizationRole::Member->value,
        'status' => OrganizationMemberStatus::Invited->value,
        'token' => hash('sha256', 'some-token'),
        'expires_at' => now()->subDay(),
    ]);

    $this->artisan('model:prune', ['--model' => [User::class]])->assertSuccessful();

    expect($organization->fresh()->users()->count())->toBe(0);
});

test('leaves non-expired pending invited users untouched', function () {
    $organization = Organization::factory()->create();
    $invitee = User::factory()->create(['password' => null]);
    $invitee->organizations()->attach($organization, [
        'role' => OrganizationRole::Member->value,
        'status' => OrganizationMemberStatus::Invited->value,
        'token' => hash('sha256', 'some-token'),
        'expires_at' => now()->addDays(7),
    ]);

    $this->artisan('model:prune', ['--model' => [User::class]])->assertSuccessful();

    expect(User::query()->whereKey($invitee->id)->exists())->toBeTrue();
});

test('leaves active members untouched regardless of a past pivot expiry', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->forOrganization($organization)->create();
    $member->organizations()->updateExistingPivot($organization->id, [
        'expires_at' => now()->subDay(),
    ]);

    $this->artisan('model:prune', ['--model' => [User::class]])->assertSuccessful();

    expect(User::query()->whereKey($member->id)->exists())->toBeTrue();
});

test('leaves users who accepted their invitation untouched', function () {
    $organization = Organization::factory()->create();
    $invitee = User::factory()->create();
    $invitee->organizations()->attach($organization, [
        'role' => OrganizationRole::Member->value,
        'status' => OrganizationMemberStatus::Invited->value,
        'token' => hash('sha256', 'some-token'),
        'expires_at' => now()->subDay(),
    ]);

    $this->artisan('model:prune', ['--model' => [User::class]])->assertSuccessful();

    expect(User::query()->whereKey($invitee->id)->exists())->toBeTrue();
});
