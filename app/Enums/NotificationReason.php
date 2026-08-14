<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationReason: string
{
    case NeedsReview = 'needs_review';
    case ForAttention = 'for_attention';
    case WantsInput = 'wants_input';

    public function label(): string
    {
        return match ($this) {
            self::NeedsReview => 'Needs your review',
            self::ForAttention => 'For your attention',
            self::WantsInput => 'Would appreciate your input',
        };
    }

    public function summary(): string
    {
        return $this->label().'.';
    }
}
