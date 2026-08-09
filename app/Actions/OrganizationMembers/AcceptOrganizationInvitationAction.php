<?php

declare(strict_types=1);

namespace App\Actions\OrganizationMembers;

use App\Enums\OrganizationMemberStatus;
use App\Models\User;
use App\Notifications\MemberJoinedNotification;
use Illuminate\Support\Facades\DB;

final class AcceptOrganizationInvitationAction
{
    /**
     * @param  array{password: string}  $attributes
     *
     * @throws \Throwable
     */
    public function handle(User $invitation, array $attributes): ?User
    {
        $accepted = DB::transaction(function () use ($invitation, $attributes): ?User {
            /** @noinspection PhpUndefinedMethodInspection */
            /** @var User|null $current */
            $current = User::query()
                ->whereKey($invitation->id)
                ->pendingInvitation()
                ->lockForUpdate()
                ->first();

            if (! $current instanceof User) {
                return null;
            }

            $current->update([
                'password' => $attributes['password'],
                'status' => OrganizationMemberStatus::Active,
                'joined_at' => now(),
                'invitation_token' => null,
                'invitation_expires_at' => null,
            ]);

            return $current;
        });

        if (! $accepted instanceof User) {
            return null;
        }

        $accepted->organization->owner()?->notify(new MemberJoinedNotification($accepted->organization, $accepted)->afterCommit());

        return $accepted;
    }
}
