export type NotifyReasonOption = {
    value: 'needs_review' | 'for_attention' | 'wants_input';
    label: string;
    desc: string;
    badge: string;
};

export const NOTIFY_REASON_OPTIONS: NotifyReasonOption[] = [
    {
        value: 'needs_review',
        label: 'Needs your review',
        desc: 'Best when the recipient should inspect or validate something.',
        badge: 'Validation',
    },
    {
        value: 'for_attention',
        label: 'For your attention',
        desc: 'Softer, for something the recipient should be aware of or take a look at.',
        badge: 'Awareness',
    },
    {
        value: 'wants_input',
        label: 'Would appreciate your input',
        desc: "For when the sender wants judgment, feedback, or another person's perspective.",
        badge: 'Collaboration',
    },
];
