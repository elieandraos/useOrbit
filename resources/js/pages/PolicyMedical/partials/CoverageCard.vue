<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import DetailField from '@/pages/Clients/partials/DetailField.vue';
import type { PolicyMedicalResource } from './policy';

defineProps<{
    policy: PolicyMedicalResource;
}>();
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Coverage</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="grid grid-cols-2 gap-x-3.5 gap-y-4">
                <DetailField label="Class" :value="policy.class_label" />
                <DetailField label="Sub-class" :value="policy.subclass" />
                <DetailField label="Type" :value="policy.type_label" />
                <DetailField
                    label="Policy number"
                    :value="policy.policy_number"
                    mono
                />
            </div>
        </CardContent>
    </Card>
</template>
