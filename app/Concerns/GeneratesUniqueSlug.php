<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Support\Str;

trait GeneratesUniqueSlug
{
    /**
     * @param  class-string  $modelClass
     */
    private function generateUniqueSlug(
        string $modelClass,
        string $value,
        int $organizationId,
        ?int $excludeId = null,
    ): string {
        $base = Str::slug($value);
        $slug = $base;
        $counter = 1;

        while (
            $modelClass::query()
                ->where('slug', $slug)
                ->where('organization_id', $organizationId)
                ->when($excludeId !== null, fn ($q) => $q->where('id', '!=', $excludeId))
                ->exists()
        ) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
