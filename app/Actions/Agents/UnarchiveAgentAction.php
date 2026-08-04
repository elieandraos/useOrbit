<?php

declare(strict_types=1);

namespace App\Actions\Agents;

use App\Enums\AgentStatus;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UnarchiveAgentAction
{
    /**
     * @throws \Throwable
     */
    public function handle(User $user, Agent $agent): Agent
    {
        return DB::transaction(function () use ($user, $agent): Agent {
            $agent->update([
                'status' => AgentStatus::Active,
                'updated_by' => $user->id,
            ]);

            return $agent->fresh();
        });
    }
}
