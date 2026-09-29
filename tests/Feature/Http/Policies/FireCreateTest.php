<?php

declare(strict_types=1);

use App\Models\User;

test('the create page offers the canonical fire subclasses', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.fire.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('PolicyFire/Create')
            ->where('subclasses', ['Building', 'Contents', 'Business interruption', 'All risk'])
        );
});
