<?php

declare(strict_types=1);

namespace App\Actions\Carriers;

use App\Enums\CarrierStatus;
use App\Models\Carrier;
use App\Models\User;
use App\Notifications\ResourceUnarchivedNotification;
use App\Support\Notifications\LeadershipRecipients;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

final readonly class UnarchiveCarrierAction
{
    public function __construct(private LeadershipRecipients $recipients) {}

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

        Notification::send(
            $this->recipients->resolve($user),
            new ResourceUnarchivedNotification($user, $unarchived)->afterCommit(),
        );

        return $unarchived;
    }
}
