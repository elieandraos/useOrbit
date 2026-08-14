<?php

declare(strict_types=1);

namespace App\Actions\OrganizationMembers;

use App\Models\User;
use App\Notifications\MemberRoleChangedNotification;
use App\Notifications\YourRoleChangedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

final class ChangeOrganizationMemberRoleAction
{
    /**
     * @param  array{role: string}  $attributes
     *
     * @throws \Throwable
     */
    public function handle(User $actor, User $member, array $attributes): User
    {
        $previousRole = $member->role;

        $updated = DB::transaction(function () use ($member, $attributes): User {
            $member->update([
                'role' => $attributes['role'],
            ]);

            return $member->fresh();
        });

        $recipients = User::query()
            ->activeInCurrentOrganization()
            ->privileged()
            ->whereNotIn('id', [$actor->id, $member->id])
            ->get();

        Notification::send(
            $recipients,
            new MemberRoleChangedNotification($actor, $updated->organization, $updated, $previousRole, $updated->role)->afterCommit(),
        );

        $updated->notify(new YourRoleChangedNotification($actor, $updated->organization, $previousRole, $updated->role)->afterCommit());

        return $updated;
    }
}
