<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import DetailField from '@/pages/Clients/partials/DetailField.vue';
import type { PolicyTravelResource } from './policy';

defineProps<{
    policy: PolicyTravelResource;
}>();
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Travel detail</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="grid grid-cols-2 gap-x-3.5 gap-y-4">
                <DetailField
                    label="Destination"
                    :value="policy.details.destination"
                />
                <DetailField
                    label="Coverage tier"
                    :value="policy.details.coverage_tier"
                />
                <DetailField
                    label="Trip start"
                    :value="policy.details.trip_start_date_formatted"
                />
                <DetailField
                    label="Trip end"
                    :value="policy.details.trip_end_date_formatted"
                />
                <DetailField
                    label="Travelers"
                    :value="policy.details.travelers"
                />
            </div>
        </CardContent>
    </Card>
</template>
