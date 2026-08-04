<?php

declare(strict_types=1);

use App\Actions\Agents\DestroyAgentAction;
use App\Models\Agent;

test('soft deletes the agent', function () {
    /** @var Agent $agent */
    $agent = Agent::factory()->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    app(DestroyAgentAction::class)->handle($agent);

    $this->assertSoftDeleted($agent);
});
