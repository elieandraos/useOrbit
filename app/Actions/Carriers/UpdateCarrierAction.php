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
     * @param  array{name: string, phone?: string|null, website?: string|null, onboarded_date: string, branch: array{street?: string|null, building_floor?: string|null, city: string, country_id?: int|null, state_id?: int|null, phone?: string|null}, contact: array{name: string, role?: string|null, email?: string|null, phone?: string|null, department?: string|null}}  $attributes
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
                'onboarded_date' => $attributes['onboarded_date'],
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

            $carrier->hqBranch()->update([
                'street' => $attributes['branch']['street'] ?? null,
                'building_floor' => $attributes['branch']['building_floor'] ?? null,
                'city' => $attributes['branch']['city'],
                'state_id' => $attributes['branch']['state_id'] ?? null,
                'country_id' => $attributes['branch']['country_id'] ?? null,
                'phone' => $attributes['branch']['phone'] ?? null,
                'contact_name' => $attributes['contact']['name'],
                'contact_role' => $attributes['contact']['role'] ?? null,
                'contact_email' => $attributes['contact']['email'] ?? null,
                'contact_phone' => $attributes['contact']['phone'] ?? null,
                'contact_department' => $attributes['contact']['department'] ?? null,
            ]);

            return $carrier->fresh();
        });
    }
}
