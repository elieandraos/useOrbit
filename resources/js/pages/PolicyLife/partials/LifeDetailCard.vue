<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import DetailField from '@/pages/Clients/partials/DetailField.vue';
import type { PolicyLifeResource } from './policy';

defineProps<{
    policy: PolicyLifeResource;
}>();
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Life detail</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="grid grid-cols-2 gap-x-3.5 gap-y-4">
                <DetailField
                    label="Sum assured"
                    :value="policy.details.sum_assured"
                    mono
                />
                <DetailField
                    label="Term"
                    :value="`${policy.details.term_years} years`"
                />
                <DetailField
                    label="Smoker"
                    :value="policy.details.smoker ? 'Yes' : 'No'"
                />
                <DetailField
                    label="Beneficiaries"
                    :value="policy.details.beneficiaries"
                    multiline
                />
            </div>
        </CardContent>
    </Card>
</template>
