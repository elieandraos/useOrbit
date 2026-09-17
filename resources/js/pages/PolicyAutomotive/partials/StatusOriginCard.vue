<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import DetailField from '@/pages/Clients/partials/DetailField.vue';
import type { PolicyAutomotiveResource } from './policy';

defineProps<{
    policy: PolicyAutomotiveResource;
}>();
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Status & origin</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="grid grid-cols-2 gap-x-3.5 gap-y-4">
                <DetailField label="Status" :value="policy.status_label" />
                <DetailField label="Lead source" :value="policy.source_label" />
            </div>
        </CardContent>
    </Card>
</template>
