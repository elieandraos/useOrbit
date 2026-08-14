<?php

declare(strict_types=1);

namespace App\Actions\Carriers;

use App\Enums\CarrierStatus;
use App\Models\Carrier;
use App\Models\User;
use App\Notifications\ResourceArchivedNotification;
use App\Support\Notifications\LeadershipRecipients;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

final readonly class ArchiveCarrierAction
{
    public function __construct(private LeadershipRecipients $recipients) {}

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

        Notification::send(
            $this->recipients->resolve($user),
            new ResourceArchivedNotification($user, $archived)->afterCommit(),
        );

        return $archived;
    }
}
