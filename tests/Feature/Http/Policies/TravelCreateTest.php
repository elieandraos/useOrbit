<?php

declare(strict_types=1);

use App\Models\User;

test('the create page offers the canonical travel subclasses', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.travel.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('PolicyTravel/Create')
            ->where('subclasses', ['Schengen', 'Worldwide', 'Student', 'Pilgrim'])
        );
});
