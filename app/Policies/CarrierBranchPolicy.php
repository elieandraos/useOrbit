<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CarrierBranch;
use App\Models\User;

final class CarrierBranchPolicy
{
    public function update(User $user, CarrierBranch $branch): bool
    {
        return $branch->carrier?->organization_id === $user->current_organization_id;
    }

    public function delete(User $user, CarrierBranch $branch): bool
    {
        return $branch->carrier?->organization_id === $user->current_organization_id;
    }
}
