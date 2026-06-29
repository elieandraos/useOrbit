<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Organization;
use App\Models\Scopes\CurrentOrganizationScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCurrentOrganization
{
    public static function bootBelongsToCurrentOrganization(): void
    {
        static::addGlobalScope(new CurrentOrganizationScope);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
