<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Enums\ClientStatus;
use App\Models\Client;
use App\Models\User;
use App\Notifications\ResourceArchivedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

final class ArchiveClientAction
{
    public function handle(User $user, Client $client): Client
    {
        $archived = DB::transaction(function () use ($user, $client): Client {
            $client->update([
                'status' => ClientStatus::Archived,
                'updated_by' => $user->id,
            ]);

            return $client->fresh();
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
