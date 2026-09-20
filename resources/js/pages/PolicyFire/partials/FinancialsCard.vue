<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import DetailField from '@/pages/Clients/partials/DetailField.vue';
import type { PolicyFireResource } from './policy';

defineProps<{
    policy: PolicyFireResource;
}>();
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Financials</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="grid grid-cols-2 gap-x-3.5 gap-y-4">
                <DetailField
                    label="Premium"
                    :value="policy.premium_amount"
                    mono
                />
                <DetailField
                    label="Discount"
                    :value="policy.discount_amount"
                    mono
                />
                <DetailField
                    label="Net premium"
                    :value="policy.net_premium"
                    mono
                />
            </div>
        </CardContent>
    </Card>
</template>
