<script setup lang="ts">
import AutomotiveDetailCard from './partials/AutomotiveDetailCard.vue';
import CoverageCard from './partials/CoverageCard.vue';
import CoveragePeriodCard from './partials/CoveragePeriodCard.vue';
import FinancialsCard from './partials/FinancialsCard.vue';
import PartiesCard from './partials/PartiesCard.vue';
import type { PolicyAutomotiveResource } from './partials/policy';
import PolicyAutomotiveDetailShell from './partials/PolicyAutomotiveDetailShell.vue';
import StatusOriginCard from './partials/StatusOriginCard.vue';

defineProps<{
    policy: PolicyAutomotiveResource;
}>();
</script>

<template>
    <PolicyAutomotiveDetailShell :policy="policy">
        <div class="grid grid-cols-1 items-start gap-5 pt-6 lg:grid-cols-2">
            <CoverageCard :policy="policy" />
            <PartiesCard :policy="policy" />
            <CoveragePeriodCard :policy="policy" />
            <FinancialsCard :policy="policy" />
            <StatusOriginCard :policy="policy" />
            <AutomotiveDetailCard :policy="policy" />
        </div>
    </PolicyAutomotiveDetailShell>
</template>
