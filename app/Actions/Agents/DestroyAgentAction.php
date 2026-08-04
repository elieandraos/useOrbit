<?php

declare(strict_types=1);

namespace App\Actions\Agents;

use App\Models\Agent;

final class DestroyAgentAction
{
    public function handle(Agent $agent): void
    {
        $agent->delete();
    }
}
