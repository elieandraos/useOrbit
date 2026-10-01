<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DetailField } from '@/components/ui/detail-field';
import type { ClientResource } from './client';

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
