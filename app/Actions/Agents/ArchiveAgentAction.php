<?php

declare(strict_types=1);

namespace App\Actions\Agents;

use App\Enums\AgentStatus;
use App\Models\Agent;
use App\Models\User;
use App\Notifications\ResourceArchivedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

final class ArchiveAgentAction
{
    /**
     * @throws \Throwable
     */
    public function handle(User $user, Agent $agent): Agent
    {
        $archived = DB::transaction(function () use ($user, $agent): Agent {
            $agent->update([
                'status' => AgentStatus::Archived,
                'updated_by' => $user->id,
            ]);

            return $agent->fresh();
        });

        $recipients = User::query()
            ->activeInCurrentOrganization()
            ->privileged()
            ->whereKeyNot($user->id)
            ->get();

        Notification::send($recipients, new ResourceArchivedNotification($user, $archived)->afterCommit());

        return $archived;
    }
}
