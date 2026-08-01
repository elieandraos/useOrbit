<script setup lang="ts">
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
            <CardTitle>Company information</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="flex flex-col gap-3.5">
                <DetailField
                    label="Company name"
                    :value="client.company_name"
                />
                <DetailField
                    label="Contact person"
                    :value="`${client.first_name} ${client.last_name}`"
                />
                <DetailField label="Phone" :value="client.phone" mono />
                <DetailField label="Email" :value="client.email" lowercase />
                <DetailField
                    label="Address"
                    :value="client.full_address"
                    multiline
                />
            </div>
        </CardContent>
    </Card>
</template>
