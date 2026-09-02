<?php

declare(strict_types=1);

use App\Models\Agent;
use App\Models\User;

test('createdBy resolves the user who created the agent', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Agent $agent */
    $agent = Agent::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    expect($agent->createdBy)->toBeInstanceOf(User::class)
        ->and($agent->createdBy->is($user))->toBeTrue();
});
