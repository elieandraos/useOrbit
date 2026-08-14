<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Enums\ClientStatus;
use App\Models\Client;
use App\Models\User;
use App\Notifications\ResourceUnarchivedNotification;
use App\Support\Notifications\LeadershipRecipients;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

final readonly class UnarchiveClientAction
{
    public function __construct(private LeadershipRecipients $recipients) {}

    /**
     * @throws \Throwable
     */
    public function handle(User $user, Client $client): Client
    {
        $unarchived = DB::transaction(function () use ($user, $client): Client {
            $client->update([
                'status' => ClientStatus::Active,
                'updated_by' => $user->id,
            ]);

            return $client->fresh();
        });

        Notification::send(
            $this->recipients->resolve($user),
            new ResourceUnarchivedNotification($user, $unarchived)->afterCommit(),
        );

        return $unarchived;
    }
}
