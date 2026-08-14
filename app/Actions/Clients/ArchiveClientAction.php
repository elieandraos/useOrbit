<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Enums\ClientStatus;
use App\Models\Client;
use App\Models\User;
use App\Notifications\ResourceArchivedNotification;
use App\Support\Notifications\LeadershipRecipients;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

final readonly class ArchiveClientAction
{
    public function __construct(private LeadershipRecipients $recipients) {}

    public function handle(User $user, Client $client): Client
    {
        $archived = DB::transaction(function () use ($user, $client): Client {
            $client->update([
                'status' => ClientStatus::Archived,
                'updated_by' => $user->id,
            ]);

            return $client->fresh();
        });

        Notification::send(
            $this->recipients->resolve($user),
            new ResourceArchivedNotification($user, $archived)->afterCommit(),
        );

        return $archived;
    }
}
