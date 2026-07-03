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
            <CardTitle>Contact</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="flex flex-col gap-3.5">
                <DetailField label="Phone" :value="client.phone" mono />
                <DetailField label="Email" :value="client.email" />
                <DetailField label="Address" :value="client.full_address" />
            </div>
        </CardContent>
    </Card>
</template>
