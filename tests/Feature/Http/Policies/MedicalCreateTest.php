<?php

declare(strict_types=1);

use App\Models\User;

test('the create page offers the canonical medical subclasses', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.medical.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('PolicyMedical/Create')
            ->where('subclasses', ['Hospitalization', 'Outpatient', 'Dental', 'Vision', 'Major medical'])
        );
});

test('the create page receives the shared and medical form options', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.medical.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('PolicyMedical/Create')
            ->hasAll(['clients', 'carriers', 'agents', 'types', 'statuses', 'sources', 'subclasses', 'coverageScopes', 'classTiers', 'genders'])
        );
});
