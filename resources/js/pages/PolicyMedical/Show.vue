<script setup lang="ts">
import CoverageCard from './partials/CoverageCard.vue';
import CoveragePeriodCard from './partials/CoveragePeriodCard.vue';
import FinancialsCard from './partials/FinancialsCard.vue';
import MedicalDetailCard from './partials/MedicalDetailCard.vue';
import PartiesCard from './partials/PartiesCard.vue';
import type { PolicyMedicalResource } from './partials/policy';
import PolicyMedicalDetailShell from './partials/PolicyMedicalDetailShell.vue';
import StatusOriginCard from './partials/StatusOriginCard.vue';

defineProps<{
    policy: PolicyMedicalResource;
}>();
</script>

<template>
    <PolicyMedicalDetailShell :policy="policy">
        <div class="grid grid-cols-1 items-start gap-5 pt-6 lg:grid-cols-2">
            <CoverageCard :policy="policy" />
            <PartiesCard :policy="policy" />
            <CoveragePeriodCard :policy="policy" />
            <FinancialsCard :policy="policy" />
            <StatusOriginCard :policy="policy" />
            <MedicalDetailCard :policy="policy" />
        </div>
    </PolicyMedicalDetailShell>
</template>
