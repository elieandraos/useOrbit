import type { BadgeVariants } from '@/components/ui/badge';

export const policyStatusTone: Record<
    string,
    NonNullable<BadgeVariants['tone']>
> = {
    active: 'success',
    cancelled: 'danger',
    frozen: 'warning',
};
