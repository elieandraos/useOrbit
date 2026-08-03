<?php

declare(strict_types=1);

namespace App\Actions\Carriers;

use App\Models\CarrierBranch;

final class DeleteCarrierBranchAction
{
    public function handle(CarrierBranch $branch): void
    {
        $branch->delete();
    }
}
