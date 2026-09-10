<?php

declare(strict_types=1);

namespace App\Enums;

enum PolicySource: string
{
    case Owner = 'owner';
    case Client = 'client';
    case Friend = 'friend';
    case Agent = 'agent';
}
