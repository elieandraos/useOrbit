<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import DetailField from '@/pages/Clients/partials/DetailField.vue';
import type { PolicyExpatResource } from './policy';

defineProps<{
    policy: PolicyExpatResource;
}>();
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Coverage period</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="grid grid-cols-2 gap-x-3.5 gap-y-4">
                <DetailField
                    label="Effective date"
                    :value="policy.effective_date_formatted"
                />
                <DetailField
                    label="Expiry date"
                    :value="policy.expiry_date_formatted"
                />
            </div>
        </CardContent>
    </Card>
</template>
