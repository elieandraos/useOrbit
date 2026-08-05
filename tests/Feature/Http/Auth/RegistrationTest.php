<?php

declare(strict_types=1);

use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'organization' => 'Test Company',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    /** @var User $user */
    $user = auth()->user();
    $organization = Organization::query()->where('name', 'Test Company')->first();
    $pivot = $user->organizations()->first()->pivot;

    expect($organization)->not->toBeNull()
        ->and($user->current_organization_id)->toBe($organization->id)
        ->and($pivot->role)->toBe(OrganizationRole::Owner)
        ->and($pivot->status)->toBe(OrganizationMemberStatus::Active);
});
