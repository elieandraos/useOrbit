<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Policy;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $policy = Policy::factory()->medical()->create();

    $this->post(route('policies.notes.store', $policy), ['body' => 'A note.'])
        ->assertRedirect(route('login'));
});

test('authenticated user gets 404 for a policy from another organization', function () {
    $user = User::factory()->withOrganization()->create();
    $otherOrganization = Organization::factory()->create();
    $policy = Policy::factory()->medical()->create(['organization_id' => $otherOrganization->id]);

    $this->actingAs($user)
        ->post(route('policies.notes.store', $policy), ['body' => 'A note.'])
        ->assertNotFound();
});

test('rejects a missing body', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.notes.store', $policy))
        ->assertInvalid(['body']);

    $this->assertDatabaseCount('notes', 0);
});

test('rejects a body exceeding the configured max length', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);
    $body = str_repeat('a', config('notes.max_length') + 1);

    $this->actingAs($user)
        ->post(route('policies.notes.store', $policy), ['body' => $body])
        ->assertInvalid(['body']);

    $this->assertDatabaseCount('notes', 0);
});

test('accepts a body exactly at the configured max length', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);
    $body = str_repeat('a', config('notes.max_length'));

    $this->actingAs($user)
        ->post(route('policies.notes.store', $policy), ['body' => $body])
        ->assertCreated();

    $this->assertDatabaseHas('notes', ['body' => $body]);
});

test('creates a note with created_by set to the acting user', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.notes.store', $policy), ['body' => 'Confirmed renewal terms with the client.'])
        ->assertCreated()
        ->assertJson([
            'body' => 'Confirmed renewal terms with the client.',
            'pinned' => false,
        ]);

    $this->assertDatabaseHas('notes', [
        'notable_type' => $policy->getMorphClass(),
        'notable_id' => $policy->id,
        'organization_id' => $user->organization_id,
        'created_by' => $user->id,
    ]);
});

test('preserves line breaks in the body', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);
    $body = "Line one.\nLine two.\nLine three.";

    $this->actingAs($user)
        ->post(route('policies.notes.store', $policy), ['body' => $body])
        ->assertCreated()
        ->assertJson(['body' => $body]);

    $this->assertDatabaseHas('notes', ['body' => $body]);
});
