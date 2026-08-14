<?php

declare(strict_types=1);

namespace App\Support\Notifications;

use App\Enums\OrganizationRole;
use App\Models\User;
use App\Support\OrganizationMembers\ActiveOrganizationRecipients;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final readonly class LeadershipRecipients
{
    public function __construct(private ActiveOrganizationRecipients $recipients) {}

    /** @return Collection<int, User> */
    public function resolve(User ...$excluding): Collection
    {
        $excludedIds = array_map(fn (User $user): int => $user->id, $excluding);

        return $this->recipients->query()
            ->whereIn('role', [OrganizationRole::Owner->value, OrganizationRole::Admin->value])
            ->when($excludedIds !== [], fn (Builder $query) => $query->whereNotIn('id', $excludedIds))
            ->get();
    }
}
