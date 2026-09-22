<script setup lang="ts">
import CoverageCard from './partials/CoverageCard.vue';
import CoveragePeriodCard from './partials/CoveragePeriodCard.vue';
import FinancialsCard from './partials/FinancialsCard.vue';
import FireDetailCard from './partials/FireDetailCard.vue';
import PartiesCard from './partials/PartiesCard.vue';
import type { PolicyFireResource } from './partials/policy';
import PolicyFireDetailShell from './partials/PolicyFireDetailShell.vue';
import StatusOriginCard from './partials/StatusOriginCard.vue';

defineProps<{
    policy: PolicyFireResource;
}>();
</script>

<template>
    <PolicyFireDetailShell :policy="policy">
        <div class="grid grid-cols-1 items-start gap-5 pt-6 lg:grid-cols-2">
            <CoverageCard :policy="policy" />
            <PartiesCard :policy="policy" />
            <CoveragePeriodCard :policy="policy" />
            <FinancialsCard :policy="policy" />
            <StatusOriginCard :policy="policy" />
            <FireDetailCard :policy="policy" />
        </div>
    </PolicyFireDetailShell>
</template>
