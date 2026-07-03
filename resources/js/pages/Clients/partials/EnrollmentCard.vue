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
        <CardHeader>
            <CardTitle>Enrollment</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="grid grid-cols-2 gap-x-3.5 gap-y-4">
                <DetailField
                    label="Enrolled"
                    :value="client.enrollment_date_formatted"
                />
                <DetailField
                    label="Lead source"
                    :value="client.lead_source_label"
                />
            </div>
        </CardContent>
    </Card>
</template>
