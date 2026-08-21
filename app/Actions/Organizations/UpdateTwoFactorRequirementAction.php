<?php

declare(strict_types=1);

namespace App\Actions\Organizations;

use App\Models\Organization;

final readonly class UpdateTwoFactorRequirementAction
{
    /**
     * @param  array{two_factor_required: bool}  $attributes
     */
    public function handle(Organization $organization, array $attributes): Organization
    {
        $organization->update([
            'two_factor_required' => $attributes['two_factor_required'],
        ]);

        return $organization;
    }
}
