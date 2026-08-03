<?php

declare(strict_types=1);

namespace App\Actions\Carriers;

use App\Concerns\GeneratesUniqueSlug;
use App\Models\Carrier;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdateCarrierAction
{
    use GeneratesUniqueSlug;

    /**
     * @param  array{name: string, phone?: string|null, website?: string|null}  $attributes
     *
     * @throws \Throwable
     */
    public function handle(User $user, Carrier $carrier, array $attributes): Carrier
    {
        return DB::transaction(function () use ($user, $carrier, $attributes): Carrier {
            $nameChanged = $attributes['name'] !== $carrier->name;

            $carrier->update([
                'name' => $attributes['name'],
                'phone' => $attributes['phone'] ?? null,
                'website' => $attributes['website'] ?? null,
                'updated_by' => $user->id,
                ...$nameChanged ? [
                    'slug' => $this->generateUniqueSlug(
                        Carrier::class,
                        $attributes['name'],
                        $user->current_organization_id,
                        $carrier->id,
                    ),
                ] : [],
            ]);

            return $carrier->fresh();
        });
    }
}
