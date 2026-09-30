<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import DetailField from '@/pages/Clients/partials/DetailField.vue';
import type { PolicyExpatResource } from './policy';

const props = defineProps<{
    policy: PolicyExpatResource;
}>();

const isInOut = props.policy.details.coverage_zone === 'in_out';
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Expat detail</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="grid grid-cols-2 gap-x-3.5 gap-y-4">
                <DetailField
                    label="Coverage zone"
                    :value="policy.details.coverage_zone_label"
                />
                <DetailField
                    v-if="isInOut"
                    label="Travel scope"
                    :value="policy.details.travel_scope"
                />
                <DetailField
                    label="Full name"
                    :value="policy.details.full_name"
                />
                <DetailField
                    label="Gender"
                    :value="policy.details.gender_label"
                />
                <DetailField
                    label="Nationality"
                    :value="policy.details.nationality"
                />
                <DetailField
                    label="Date of birth"
                    :value="policy.details.date_of_birth_formatted"
                />
                <DetailField label="Phone" :value="policy.details.phone" mono />
                <DetailField
                    label="Country"
                    :value="policy.details.country_name"
                />
                <DetailField
                    label="Visa expiry"
                    :value="policy.details.visa_expiry_date_formatted"
                />
            </div>
        </CardContent>
    </Card>
</template>
