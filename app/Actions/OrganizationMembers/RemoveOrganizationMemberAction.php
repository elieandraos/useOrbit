<?php

declare(strict_types=1);

namespace App\Actions\OrganizationMembers;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final class RemoveOrganizationMemberAction
{
    /**
     * @throws \Throwable
     */
    public function handle(User $user, User $member): User
    {
        return DB::transaction(function () use ($user, $member): User {
            $member->organizations()->detach($user->current_organization_id);

            if ($member->current_organization_id === $user->current_organization_id) {
                $member->update(['current_organization_id' => null]);
            }

            return $member->fresh();
        });
    }
}
