<?php

declare(strict_types=1);

namespace App\Support\Notifications;

use App\Enums\OrganizationRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class LeadershipRecipients
{
    /** @return Collection<int, User> */
    public function resolve(User ...$excluding): Collection
    {
        $excludedIds = array_map(fn (User $user): int => $user->id, $excluding);

        return User::query()
            ->activeInCurrentOrganization()
            ->whereIn('role', [OrganizationRole::Owner->value, OrganizationRole::Admin->value])
            ->when($excludedIds !== [], fn (Builder $query) => $query->whereNotIn('id', $excludedIds))
            ->get();
    }
}
