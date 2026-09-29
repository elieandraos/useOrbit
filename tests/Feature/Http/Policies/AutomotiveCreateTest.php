<?php

declare(strict_types=1);

use App\Models\User;

test('the create page offers the canonical automotive subclasses', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.automotive.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('PolicyAutomotive/Create')
            ->where('subclasses', ['Third Party Liability', 'All Risk', 'Compulsory'])
        );
});
