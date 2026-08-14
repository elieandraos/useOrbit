<?php

declare(strict_types=1);

use App\Enums\NotificationReason;

test('each reason has its own label and summary', function (NotificationReason $reason, string $label, string $summary) {
    expect($reason->label())->toBe($label)
        ->and($reason->summary())->toBe($summary);
})->with([
    'needs_review' => [NotificationReason::NeedsReview, 'Needs your review', 'Needs your review.'],
    'for_attention' => [NotificationReason::ForAttention, 'For your attention', 'For your attention.'],
    'wants_input' => [NotificationReason::WantsInput, 'Would appreciate your input', 'Would appreciate your input.'],
]);
