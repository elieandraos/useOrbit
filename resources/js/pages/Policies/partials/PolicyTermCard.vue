<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import DetailField from '@/pages/Clients/partials/DetailField.vue';
import type { PolicyResource } from '@/types/policy';

defineProps<{
    policy: PolicyResource;
}>();
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Term & source</CardTitle>
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
                <DetailField label="Lead source" :value="policy.source_label" />
            </div>
        </CardContent>
    </Card>
</template>
