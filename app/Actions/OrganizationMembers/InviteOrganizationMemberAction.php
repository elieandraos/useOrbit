<?php

declare(strict_types=1);

namespace App\Actions\OrganizationMembers;

use App\Enums\OrganizationMemberStatus;
use App\Models\User;
use App\Notifications\OrganizationInvitationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class InviteOrganizationMemberAction
{
    /**
     * @param  array{name: string, email: string, role: string}  $attributes
     *
     * @throws \Throwable
     */
    public function handle(User $invitedBy, array $attributes): User
    {
        $token = Str::random(40);

        $invitee = DB::transaction(function () use ($invitedBy, $attributes, $token): User {
            /** @var User $invitee */
            $invitee = User::query()->create([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'password' => null,
            ]);

            $invitee->organizations()->attach($invitedBy->current_organization_id, [
                'role' => $attributes['role'],
                'status' => OrganizationMemberStatus::Invited->value,
                'invited_by' => $invitedBy->id,
                'token' => hash('sha256', $token),
                'expires_at' => now()->addDays(7),
            ]);

            return $invitee;
        });

        $invitee->notify(
            new OrganizationInvitationNotification($invitedBy->currentOrganization, $invitedBy, $token)->afterCommit(),
        );

        return $invitee;
    }
}
