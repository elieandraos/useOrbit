<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import DetailField from '@/pages/Clients/partials/DetailField.vue';
import type { PolicyMedicalResource } from './policy';

const props = defineProps<{
    policy: PolicyMedicalResource;
}>();

const coInsuranceValue = props.policy.details.co_insurance
    ? `Yes (${props.policy.details.co_insurance_share}%)`
    : 'No';
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Medical detail</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="grid grid-cols-2 gap-x-3.5 gap-y-4">
                <DetailField
                    label="Coverage scope"
                    :value="policy.details.coverage_scope_label"
                />
                <DetailField
                    label="Class"
                    :value="policy.details.class_tier_label"
                />
                <DetailField label="Co-insurance" :value="coInsuranceValue" />
                <DetailField
                    label="Guaranteed renewable"
                    :value="policy.details.guaranteed_renewable ? 'Yes' : 'No'"
                />

                <template v-if="policy.type === 'single'">
                    <DetailField
                        label="Insured"
                        :value="policy.details.insured_full_name"
                    />
                    <DetailField
                        label="Date of birth"
                        :value="policy.details.insured_date_of_birth_formatted"
                    />
                    <DetailField
                        label="Gender"
                        :value="policy.details.insured_gender_label"
                    />
                    <DetailField
                        label="Smoker"
                        :value="policy.details.insured_smoker ? 'Yes' : 'No'"
                    />
                </template>
                <template v-else>
                    <DetailField
                        label="Covered members"
                        :value="`${policy.insureds.length}`"
                    />
                </template>
            </div>
        </CardContent>
    </Card>
</template>
