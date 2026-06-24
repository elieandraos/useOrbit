<?php

declare(strict_types=1);

namespace App\Enums;

enum LeadSource: string
{
    case Referral = 'referral';
    case Website = 'website';
    case SocialMedia = 'social_media';
    case Partner = 'partner';
    case WalkIn = 'walk_in';
    case ColdCall = 'cold_call';
}
