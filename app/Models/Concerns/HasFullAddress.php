<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasFullAddress
{
    protected function fullAddress(): Attribute
    {
        return Attribute::make(
            get: function () {
                $stateName = $this->relationLoaded('state') ? $this->state?->name : null;
                $countryName = $this->relationLoaded('country') ? $this->country?->name : null;

                return collect([$this->street, $this->building_floor, $this->city, $stateName, $countryName])
                    ->filter()
                    ->implode("\n");
            },
        );
    }
}
