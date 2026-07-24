<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property int $organization_id
 */
interface Notable
{
    public function notes(): MorphMany;
}
