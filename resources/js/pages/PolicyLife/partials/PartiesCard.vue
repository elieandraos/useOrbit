<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import DetailField from '@/pages/Clients/partials/DetailField.vue';
import type { PolicyLifeResource } from './policy';

defineProps<{
    policy: PolicyLifeResource;
}>();
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Parties</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="grid grid-cols-2 gap-x-3.5 gap-y-4">
                <DetailField label="Client" :value="policy.client.full_name" />
                <DetailField
                    label="Insurance company"
                    :value="policy.carrier.name"
                />
                <DetailField label="Agent" :value="policy.agent?.full_name" />
            </div>
        </CardContent>
    </Card>
</template>
