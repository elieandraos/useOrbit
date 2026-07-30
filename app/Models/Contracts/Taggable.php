<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @property int $organization_id
 */
interface Taggable
{
    public function tags(): MorphToMany;

    public static function ownerColumn(): ?string;

    public static function ownerIdColumn(): ?string;
}
