<?php

declare(strict_types=1);

use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->post(route('tags.store'), ['name' => 'Medicare'])
        ->assertRedirect(route('login'));
});

test('rejects a blank name', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->post(route('tags.store'), ['name' => ''])
        ->assertInvalid(['name']);

    $this->assertDatabaseCount('tags', 0);
});

test('creates a tag scoped to the acting user\'s organization with created_by set', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->post(route('tags.store'), ['name' => 'Medicare'])
        ->assertCreated()
        ->assertJson([
            'name' => 'Medicare',
            'usage_count' => 0,
        ]);

    $this->assertDatabaseHas('tags', [
        'organization_id' => $user->organization_id,
        'created_by' => $user->id,
    ]);
});
