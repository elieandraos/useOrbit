<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrganizationRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property OrganizationRole $role
 * @property string $status
 */
final class OrganizationMember extends Pivot
{
    protected function casts(): array
    {
        return [
            'role' => OrganizationRole::class,
        ];
    }
}
