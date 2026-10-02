<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 */
#[Fillable(['code', 'name'])]
final class Currency extends Model
{
    /** @use HasFactory<CurrencyFactory> */
    use HasFactory;
}
