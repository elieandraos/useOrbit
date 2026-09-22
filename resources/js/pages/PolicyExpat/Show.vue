<script setup lang="ts">
import CoverageCard from './partials/CoverageCard.vue';
import CoveragePeriodCard from './partials/CoveragePeriodCard.vue';
import ExpatDetailCard from './partials/ExpatDetailCard.vue';
import FinancialsCard from './partials/FinancialsCard.vue';
import PartiesCard from './partials/PartiesCard.vue';
import type { PolicyExpatResource } from './partials/policy';
import PolicyExpatDetailShell from './partials/PolicyExpatDetailShell.vue';
import StatusOriginCard from './partials/StatusOriginCard.vue';

defineProps<{
    policy: PolicyExpatResource;
}>();
</script>

<template>
    <PolicyExpatDetailShell :policy="policy">
        <div class="grid grid-cols-1 items-start gap-5 pt-6 lg:grid-cols-2">
            <CoverageCard :policy="policy" />
            <PartiesCard :policy="policy" />
            <CoveragePeriodCard :policy="policy" />
            <FinancialsCard :policy="policy" />
            <StatusOriginCard :policy="policy" />
            <ExpatDetailCard :policy="policy" />
        </div>
    </PolicyExpatDetailShell>
</template>
