<?php

declare(strict_types=1);

use App\Models\User;

test('the create page offers the canonical life subclasses', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.life.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('PolicyLife/Create')
            ->where('subclasses', ['Term', 'Whole life', 'Endowment', 'Group life'])
        );
});
