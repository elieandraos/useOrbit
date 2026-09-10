<?php

declare(strict_types=1);

namespace App\Enums;

enum MedicalCoverageScope: string
{
    case In = 'in';
    case InOut = 'in_out';
}
