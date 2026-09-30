<?php

declare(strict_types=1);

use App\Enums\TravelCoverageTier;
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

test('the create page receives the shared and travel form options', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.travel.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('PolicyTravel/Create')
            ->hasAll(['clients', 'carriers', 'agents', 'types', 'statuses', 'sources', 'subclasses', 'coverageTiers'])
        );
});

test('the create page offers the travel coverage tiers', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.travel.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('coverageTiers', TravelCoverageTier::all()));
});
