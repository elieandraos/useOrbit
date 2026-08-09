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
    public function handle(User $member, array $attributes): User
    {
        return DB::transaction(function () use ($member, $attributes): User {
            $member->update([
                'role' => $attributes['role'],
            ]);

            return $member->fresh();
        });
    }
}
