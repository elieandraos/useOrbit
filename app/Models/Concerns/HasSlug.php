<?php

declare(strict_types=1);

namespace App\Models\Concerns;

trait HasSlug
{
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
