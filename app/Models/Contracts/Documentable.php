<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property int $organization_id
 */
interface Documentable
{
    public function documents(): MorphMany;

    public function documentableKind(): string;

    public function documentableName(): string;
}
