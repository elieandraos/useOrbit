<?php

declare(strict_types=1);

namespace App\Actions\OrganizationMembers;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ChangeOrganizationMemberRoleAction
{
    /**
     * @param  array{role: string}  $attributes
     *
     * @throws \Throwable
     */
    public function handle(User $user, User $member, array $attributes): User
    {
        return DB::transaction(function () use ($user, $member, $attributes): User {
            $member->organizations()->updateExistingPivot($user->current_organization_id, [
                'role' => $attributes['role'],
            ]);

            return $member->fresh();
        });
    }
}
