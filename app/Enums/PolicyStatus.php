<?php

declare(strict_types=1);

namespace App\Enums;

enum PolicyStatus: string
{
    case Active = 'active';
    case Cancelled = 'cancelled';
    case Frozen = 'frozen';
}
