<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Enums\ClientStatus;
use App\Models\Client;
use App\Models\User;
use App\Notifications\ResourceUnarchivedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

final class UnarchiveClientAction
{
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

        $recipients = User::query()
            ->activeInCurrentOrganization()
            ->privileged()
            ->whereKeyNot($user->id)
            ->get();

        Notification::send($recipients, new ResourceUnarchivedNotification($user, $unarchived)->afterCommit());

        return $unarchived;
    }
}
