<script setup lang="ts">
import CoverageCard from './partials/CoverageCard.vue';
import CoveragePeriodCard from './partials/CoveragePeriodCard.vue';
import FinancialsCard from './partials/FinancialsCard.vue';
import PartiesCard from './partials/PartiesCard.vue';
import type { PolicyTravelResource } from './partials/policy';
import PolicyTravelDetailShell from './partials/PolicyTravelDetailShell.vue';
import StatusOriginCard from './partials/StatusOriginCard.vue';
import TravelDetailCard from './partials/TravelDetailCard.vue';

defineProps<{
    policy: PolicyTravelResource;
}>();
</script>

<template>
    <PolicyTravelDetailShell :policy="policy">
        <div class="grid grid-cols-1 items-start gap-5 pt-6 lg:grid-cols-2">
            <CoverageCard :policy="policy" />
            <PartiesCard :policy="policy" />
            <CoveragePeriodCard :policy="policy" />
            <FinancialsCard :policy="policy" />
            <StatusOriginCard :policy="policy" />
            <TravelDetailCard :policy="policy" />
        </div>
    </PolicyTravelDetailShell>
</template>
