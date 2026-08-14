<?php

declare(strict_types=1);

namespace App\Actions\Carriers;

use App\Enums\CarrierStatus;
use App\Models\Carrier;
use App\Models\User;
use App\Notifications\ResourceArchivedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

final class ArchiveCarrierAction
{
    /**
     * @throws \Throwable
     */
    public function handle(User $user, Carrier $carrier): Carrier
    {
        $archived = DB::transaction(function () use ($user, $carrier): Carrier {
            $carrier->update([
                'status' => CarrierStatus::Archived,
                'updated_by' => $user->id,
            ]);

            return $carrier->fresh();
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
