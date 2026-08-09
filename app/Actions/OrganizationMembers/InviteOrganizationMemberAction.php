<?php

declare(strict_types=1);

namespace App\Actions\OrganizationMembers;

use App\Enums\OrganizationMemberStatus;
use App\Models\User;
use App\Notifications\OrganizationInvitationNotification;
use App\Support\Tenancy\OrganizationContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class InviteOrganizationMemberAction
{
    public function __construct(private readonly OrganizationContext $organizationContext) {}

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
                'organization_id' => $this->organizationContext->id(),
                'role' => $attributes['role'],
                'status' => OrganizationMemberStatus::Invited,
                'invited_by' => $invitedBy->id,
                'invitation_token' => hash('sha256', $token),
                'invitation_expires_at' => now()->addDays(7),
            ]);

            return $invitee;
        });

        $invitee->notify(
            new OrganizationInvitationNotification($invitedBy->organization, $invitedBy, $token)->afterCommit(),
        );

        return $invitee;
    }
}
