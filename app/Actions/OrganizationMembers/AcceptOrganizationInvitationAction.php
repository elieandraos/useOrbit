<?php

declare(strict_types=1);

namespace App\Actions\OrganizationMembers;

use App\Enums\OrganizationMemberStatus;
use App\Models\OrganizationMember;
use App\Notifications\MemberJoinedNotification;
use Illuminate\Support\Facades\DB;

final class AcceptOrganizationInvitationAction
{
    /**
     * @param  array{password: string}  $attributes
     *
     * @throws \Throwable
     */
    public function handle(OrganizationMember $invitation, array $attributes): ?OrganizationMember
    {
        $accepted = DB::transaction(function () use ($invitation, $attributes): ?OrganizationMember {
            /** @noinspection PhpUndefinedMethodInspection */
            /** @var OrganizationMember|null $current */
            $current = OrganizationMember::query()
                ->whereKey($invitation->id)
                ->pendingInvitation()
                ->lockForUpdate()
                ->first();

            if (! $current instanceof OrganizationMember) {
                return null;
            }

            $current->update([
                'status' => OrganizationMemberStatus::Active->value,
                'joined_at' => now(),
                'token' => null,
                'expires_at' => null,
            ]);

            $current->user->update([
                'password' => $attributes['password'],
                'current_organization_id' => $current->organization_id,
            ]);

            return $current;
        });

        if (! $accepted instanceof OrganizationMember) {
            return null;
        }

        $accepted->organization->owner()?->notify(new MemberJoinedNotification($accepted->organization, $accepted->user)->afterCommit());

        return $accepted;
    }
}
