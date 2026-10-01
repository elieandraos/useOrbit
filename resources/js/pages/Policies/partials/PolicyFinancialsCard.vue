<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { StatCell } from '@/components/ui/stat-cell';
import type { PolicyResource } from '@/types/policy';

defineProps<{
    policy: PolicyResource;
}>();
</script>

<template>
    <Card>
        <CardHeader bordered>
            <CardTitle>Financials</CardTitle>
        </CardHeader>
        <CardContent class="p-0">
            <div
                class="grid grid-cols-1 divide-y divide-border-subtle sm:grid-cols-3 sm:divide-x sm:divide-y-0"
            >
                <StatCell label="Premium" :value="policy.premium_amount" />
                <StatCell label="Discount" :value="policy.discount_amount" />
                <StatCell label="Net premium" :value="policy.net_premium" />
            </div>
        </CardContent>
    </Card>
</template>
