<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 */
#[Fillable(['name'])]
final class Country extends Model
{
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }
}
