<?php

declare(strict_types=1);

namespace App\Actions\Carriers;

use App\Enums\CarrierStatus;
use App\Models\Carrier;
use App\Models\User;
use App\Notifications\ResourceUnarchivedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

final class UnarchiveCarrierAction
{
    /**
     * @throws \Throwable
     */
    public function handle(User $user, Carrier $carrier): Carrier
    {
        $unarchived = DB::transaction(function () use ($user, $carrier): Carrier {
            $carrier->update([
                'status' => CarrierStatus::Active,
                'updated_by' => $user->id,
            ]);

            return $carrier->fresh();
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
