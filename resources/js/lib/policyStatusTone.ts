import type { BadgeVariants } from '@/components/ui/badge';
import type { PolicyDisplayStatus } from '@/types/policy';

export const policyStatusTone: Record<
    PolicyDisplayStatus,
    NonNullable<BadgeVariants['tone']>
> = {
    upcoming: 'info',
    in_force: 'success',
    expired: 'neutral',
    cancelled: 'danger',
    frozen: 'warning',
};
