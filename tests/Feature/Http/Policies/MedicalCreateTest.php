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
