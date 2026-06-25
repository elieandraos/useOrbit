<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Scopes\CurrentOrganizationScope;

trait BelongsToCurrentOrganization
{
    public static function bootBelongsToCurrentOrganization(): void
    {
        static::addGlobalScope(new CurrentOrganizationScope);
    }
}
