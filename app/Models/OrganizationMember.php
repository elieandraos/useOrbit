<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * @property OrganizationRole $role
 * @property OrganizationMemberStatus $status
 * @property Carbon|null $joined_at
 * @property Carbon|null $expires_at
 */
final class OrganizationMember extends Pivot
{
    protected function casts(): array
    {
        return [
            'role' => OrganizationRole::class,
            'status' => OrganizationMemberStatus::class,
            'joined_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
