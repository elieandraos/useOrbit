<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import DetailField from '@/pages/Clients/partials/DetailField.vue';
import type { PolicyAutomotiveResource } from './policy';

const props = defineProps<{
    policy: PolicyAutomotiveResource;
}>();

const isAllRisk = props.policy.subclass === 'All Risk';
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Automotive detail</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="grid grid-cols-2 gap-x-3.5 gap-y-4">
                <DetailField
                    label="Plate number"
                    :value="policy.details.plate_number"
                    mono
                />
                <DetailField label="Make" :value="policy.details.make" />
                <DetailField label="Model" :value="policy.details.model" />
                <DetailField label="Year" :value="`${policy.details.year}`" />
                <DetailField label="VIN" :value="policy.details.vin" mono />
                <DetailField label="Color" :value="policy.details.color" />

                <template v-if="isAllRisk">
                    <DetailField
                        label="Valuation"
                        :value="policy.details.valuation_amount"
                        mono
                    />
                    <DetailField
                        label="Valuation source"
                        :value="policy.details.valuation_source"
                    />
                </template>
            </div>
        </CardContent>
    </Card>
</template>
