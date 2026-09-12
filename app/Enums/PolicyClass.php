<?php

declare(strict_types=1);

namespace App\Enums;

enum PolicyClass: string
{
    case Medical = 'medical';
    case Automotive = 'automotive';
    case Expat = 'expat';
    case Fire = 'fire';
    case Life = 'life';
    case Travel = 'travel';
}
