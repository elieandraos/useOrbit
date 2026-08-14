<?php

declare(strict_types=1);

namespace App\Actions\Agents;

use App\Enums\AgentStatus;
use App\Models\Agent;
use App\Models\User;
use App\Notifications\ResourceUnarchivedNotification;
use App\Support\Notifications\LeadershipRecipients;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

final readonly class UnarchiveAgentAction
{
    public function __construct(private LeadershipRecipients $recipients) {}

    /**
     * @throws \Throwable
     */
    public function handle(User $user, Agent $agent): Agent
    {
        $unarchived = DB::transaction(function () use ($user, $agent): Agent {
            $agent->update([
                'status' => AgentStatus::Active,
                'updated_by' => $user->id,
            ]);

            return $agent->fresh();
        });

        Notification::send(
            $this->recipients->resolve($user),
            new ResourceUnarchivedNotification($user, $unarchived)->afterCommit(),
        );

        return $unarchived;
    }
}
