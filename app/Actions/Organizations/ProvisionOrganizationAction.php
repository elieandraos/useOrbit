<?php

declare(strict_types=1);

namespace App\Actions\Organizations;

use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\OrganizationInvitationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class ProvisionOrganizationAction
{
    /**
     * @param  array{organization: string, name: string, email: string}  $attributes
     * @return array{owner: User, token: string}
     *
     * @throws \Throwable
     */
    public function handle(array $attributes): array
    {
        $token = Str::random(40);

        [$organization, $owner] = DB::transaction(function () use ($attributes, $token): array {
            $organization = Organization::query()->create([
                'name' => $attributes['organization'],
            ]);

            /** @var User $owner */
            $owner = User::query()->create([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'password' => null,
                'organization_id' => $organization->id,
                'role' => OrganizationRole::Owner,
                'status' => OrganizationMemberStatus::Invited,
                'invited_by' => null,
                'invitation_token' => hash('sha256', $token),
                'invitation_expires_at' => now()->addDays(7),
            ]);

            return [$organization, $owner];
        });

        $owner->notify(new OrganizationInvitationNotification($organization, null, $token)->afterCommit());

        return ['owner' => $owner, 'token' => $token];
    }
}
