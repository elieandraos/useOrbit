<?php

declare(strict_types=1);

namespace App\Actions\Carriers;

use App\Enums\CarrierStatus;
use App\Models\Carrier;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UnarchiveCarrierAction
{
    /**
     * @throws \Throwable
     */
    public function handle(User $user, Carrier $carrier): Carrier
    {
        return DB::transaction(function () use ($user, $carrier): Carrier {
            $carrier->update([
                'status' => CarrierStatus::Active,
                'updated_by' => $user->id,
            ]);

            return $carrier->fresh();
        });
    }
}
