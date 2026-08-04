<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { AgentResource } from './agent';
import DetailField from './DetailField.vue';

defineProps<{
    agent: AgentResource;
}>();
</script>

<template>
    <Card>
        <CardHeader bordered>
            <CardTitle>Contact</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="flex flex-col gap-3.5">
                <DetailField label="Phone" :value="agent.phone" mono />
                <DetailField label="Email" :value="agent.email" lowercase />
                <DetailField
                    label="Address"
                    :value="agent.full_address"
                    multiline
                />
            </div>
        </CardContent>
    </Card>
</template>
