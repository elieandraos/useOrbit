<?php

declare(strict_types=1);

namespace App\Actions\Organizations;

use App\Models\Organization;

final readonly class UpdateOrganizationDetailsAction
{
    /**
     * @param  array{name: string, default_country_id: string|null, default_currency_id: string|null}  $attributes
     */
    public function handle(Organization $organization, array $attributes): Organization
    {
        $organization->update($attributes);

        return $organization;
    }
}
