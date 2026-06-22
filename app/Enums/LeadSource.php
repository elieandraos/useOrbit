<?php

namespace App\Enums;

enum LeadSource: string
{
    case Referral = 'referral';
    case Website = 'website';
    case ColdCall = 'cold_call';
    case SocialMedia = 'social_media';
}
