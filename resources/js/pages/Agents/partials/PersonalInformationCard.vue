<script setup lang="ts">
import Badge from '@/components/ui/badge/Badge.vue';
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
            <CardTitle>Personal information</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="grid grid-cols-2 gap-x-3.5 gap-y-4">
                <DetailField label="First name" :value="agent.first_name" />
                <DetailField label="Last name" :value="agent.last_name" />
                <div class="flex min-w-0 flex-col gap-1">
                    <p
                        class="font-mono text-[10.5px] tracking-[0.06em] text-tertiary uppercase"
                    >
                        Date of birth
                    </p>
                    <div class="flex items-center gap-2">
                        <p class="truncate text-[13.5px] text-primary">
                            {{ agent.date_of_birth_formatted }}
                        </p>
                        <Badge tone="success">{{ agent.age }} yrs</Badge>
                    </div>
                </div>
                <DetailField
                    label="Joined"
                    :value="agent.joined_at_formatted"
                />
            </div>
        </CardContent>
    </Card>
</template>
