<?php

declare(strict_types=1);

namespace App\Actions\Carriers;

use App\Concerns\GeneratesUniqueSlug;
use App\Enums\CarrierStatus;
use App\Models\Carrier;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreateCarrierAction
{
    use GeneratesUniqueSlug;

    /**
     * @param  array{name: string, phone?: string|null, website?: string|null, branch: array{street?: string|null, building_floor?: string|null, city: string, country_id?: int|null, state_id?: int|null}, contact: array{name: string, role?: string|null, email?: string|null, phone?: string|null}}  $attributes
     *
     * @throws \Throwable
     */
    public function handle(User $user, array $attributes): Carrier
    {
        return DB::transaction(function () use ($user, $attributes): Carrier {
            $slug = $this->generateUniqueSlug(
                Carrier::class,
                $attributes['name'],
                $user->current_organization_id,
            );

            /** @var Carrier $carrier */
            $carrier = Carrier::query()->create([
                'name' => $attributes['name'],
                'phone' => $attributes['phone'] ?? null,
                'website' => $attributes['website'] ?? null,
                'status' => CarrierStatus::Active,
                'organization_id' => $user->current_organization_id,
                'slug' => $slug,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $carrier->branches()->create([
                'street' => $attributes['branch']['street'] ?? null,
                'building_floor' => $attributes['branch']['building_floor'] ?? null,
                'city' => $attributes['branch']['city'],
                'state_id' => $attributes['branch']['state_id'] ?? null,
                'country_id' => $attributes['branch']['country_id'] ?? null,
                'contact_name' => $attributes['contact']['name'],
                'contact_role' => $attributes['contact']['role'] ?? null,
                'contact_email' => $attributes['contact']['email'] ?? null,
                'contact_phone' => $attributes['contact']['phone'] ?? null,
            ]);

            return $carrier;
        });
    }
}
