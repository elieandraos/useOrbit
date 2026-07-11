<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Enums\ClientStatus;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ArchiveClientAction
{
    public function handle(User $user, Client $client): Client
    {
        return DB::transaction(function () use ($user, $client): Client {
            $client->update([
                'status' => ClientStatus::Archived,
                'updated_by' => $user->id,
            ]);

            return $client->fresh();
        });
    }
}
