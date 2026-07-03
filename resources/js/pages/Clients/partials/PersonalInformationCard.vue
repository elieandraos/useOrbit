<script setup lang="ts">
import Badge from '@/components/ui/badge/Badge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { ClientResource } from './client';
import DetailField from './DetailField.vue';

defineProps<{
    client: ClientResource;
}>();
</script>

<template>
    <Card>
        <CardHeader bordered>
            <CardTitle>Personal information</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="grid grid-cols-2 gap-x-3.5 gap-y-4">
                <DetailField label="First name" :value="client.first_name" />
                <DetailField label="Middle name" :value="client.middle_name" />
                <DetailField label="Last name" :value="client.last_name" />
                <DetailField
                    label="Mother's name"
                    :value="client.mothers_name"
                />
                <div class="flex min-w-0 flex-col gap-1">
                    <p
                        class="font-mono text-[10.5px] tracking-[0.06em] text-tertiary uppercase"
                    >
                        Date of birth
                    </p>
                    <div class="flex items-center gap-2">
                        <p class="truncate text-[13.5px] text-primary">
                            {{ client.date_of_birth_formatted }}
                        </p>
                        <Badge tone="success">{{ client.age }} yrs</Badge>
                    </div>
                </div>
                <DetailField label="Gender" :value="client.gender_label" />
            </div>
        </CardContent>
    </Card>
</template>
