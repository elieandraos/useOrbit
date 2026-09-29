<?php

declare(strict_types=1);

use App\Models\User;

test('the create page offers the canonical expat subclasses', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.expat.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('PolicyExpat/Create')
            ->where('subclasses', ['Worldwide', 'Schengen', 'GCC', 'Student'])
        );
});

test('the create page receives the shared and expat form options', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.expat.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('PolicyExpat/Create')
            ->hasAll(['clients', 'carriers', 'agents', 'types', 'statuses', 'sources', 'subclasses', 'coverageZones', 'genders', 'countries'])
        );
});
