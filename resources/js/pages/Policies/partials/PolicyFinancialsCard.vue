<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card';
import { StatCell } from '@/components/ui/stat-cell';
import { formatMoney } from '@/lib/money';
import type { PolicyResource } from '@/types/policy';

defineProps<{
    policy: PolicyResource;
}>();
</script>

<template>
    <Card>
        <CardContent class="p-0">
            <div
                class="grid grid-cols-1 divide-y divide-border-subtle sm:grid-cols-3 sm:divide-x sm:divide-y-0"
            >
                <StatCell
                    label="Premium"
                    :value="
                        formatMoney(policy.premium_amount, policy.currency_code)
                    "
                />
                <StatCell
                    label="Discount"
                    :value="
                        formatMoney(
                            policy.discount_amount,
                            policy.currency_code,
                        )
                    "
                />
                <StatCell
                    label="Net premium"
                    :value="
                        formatMoney(policy.net_premium, policy.currency_code)
                    "
                />
            </div>
        </CardContent>
    </Card>
</template>
