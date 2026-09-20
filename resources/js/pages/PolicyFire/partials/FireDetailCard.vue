<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import DetailField from '@/pages/Clients/partials/DetailField.vue';
import type { PolicyFireResource } from './policy';

defineProps<{
    policy: PolicyFireResource;
}>();
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Fire detail</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="grid grid-cols-2 gap-x-3.5 gap-y-4">
                <DetailField
                    label="Property type"
                    :value="policy.details.property_type"
                />
                <DetailField
                    label="Floor area"
                    :value="`${policy.details.floor_area} m²`"
                />
                <DetailField
                    label="Year built"
                    :value="
                        policy.details.year_built
                            ? `${policy.details.year_built}`
                            : null
                    "
                />
                <DetailField
                    label="Sum insured"
                    :value="policy.details.sum_insured"
                    mono
                />
                <DetailField label="Street" :value="policy.details.street" />
                <DetailField
                    label="Building / Floor"
                    :value="policy.details.building_floor"
                />
                <DetailField label="City" :value="policy.details.city" />
                <DetailField
                    label="Governorate"
                    :value="policy.details.state_name"
                />
                <DetailField
                    label="Country"
                    :value="policy.details.country_name"
                />
            </div>
        </CardContent>
    </Card>
</template>
