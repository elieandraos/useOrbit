<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { CarrierResource } from './carrier';
import DetailField from './DetailField.vue';

defineProps<{
    carrier: CarrierResource;
}>();
</script>

<template>
    <Card>
        <CardHeader bordered>
            <CardTitle>Contact</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="flex flex-col gap-3.5">
                <DetailField label="Phone" :value="carrier.phone" mono />
                <DetailField
                    label="Website"
                    :value="carrier.website"
                    accent
                    lowercase
                />
            </div>
        </CardContent>
    </Card>
</template>
